#!/usr/bin/env bash
#
# demo-db.sh — reset the local demo database from the SQL dump.
#
# The repo only contains 14 of the 30 migrations the schema needs, so
# `migrate:fresh` cannot rebuild it. The dump (admin-rapid.sql) IS the schema
# source. This script:
#   1. (re)creates the `admin-rapid` DB and imports the dump
#   2. drops 7 legacy 2021 tables with zero references in app/
#   3. prunes their rows from the `migrations` table
#   4. applies the migrations added since the dump was taken
#   5. clones the schema into `admin_rapid_test` for phpunit
#   6. runs the seeders (test accounts, pricing, demo cases)
#
# Usage: ./scripts/demo-db.sh
# Requires: mysql running locally, root with no password (see .env), php+artisan.

set -euo pipefail
cd "$(dirname "$0")/.."

# Prefer PHP 8.3 (Laravel 10-supported) when available.
PHP_BIN="${PHP_BIN:-$([ -x /opt/homebrew/opt/php@8.3/bin/php ] && echo /opt/homebrew/opt/php@8.3/bin/php || echo php)}"

MYSQL="mysql -h 127.0.0.1 -P 3306 -u root"
DB='admin-rapid'
TEST_DB='admin_rapid_test'
DUMP='admin-rapid.sql'

[ -f "$DUMP" ] || { echo "ERROR: $DUMP not found in repo root" >&2; exit 1; }

echo "==> Recreating \`$DB\` and importing $DUMP"
$MYSQL -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$MYSQL "$DB" < "$DUMP"

LEGACY_TABLES=(user_forms user_form_details user_form_files payment_settings cities states countries)
# NOTE: user_form_schedulings looks legacy but is the LIVE appointments table — keep it.

echo "==> Dropping legacy tables: ${LEGACY_TABLES[*]}"
DROP_SQL="SET FOREIGN_KEY_CHECKS=0;"
for t in "${LEGACY_TABLES[@]}"; do
  DROP_SQL+=" DROP TABLE IF EXISTS \`$t\`;"
done
DROP_SQL+=" SET FOREIGN_KEY_CHECKS=1;"
$MYSQL "$DB" -e "$DROP_SQL"

echo "==> Pruning legacy rows from migrations table"
$MYSQL "$DB" -e "DELETE FROM migrations WHERE migration IN (
  '2025_04_19_090753_create_user_forms_table',
  '2025_04_19_090906_create_user_form_details_table',
  '2025_04_26_203916_create_user_form_files_table',
  '2025_05_13_142048_create_payment_settings_table',
  '2025_04_19_234402_create_cities_table',
  '2025_04_19_234356_create_states_table',
  '2025_04_19_233053_create_countries_table',
  '2025_05_21_212309_add_status_to_user_forms',
  '2025_05_22_231928_update_meta_value_user_form_details'
);"

echo "==> Aligning uploaded_files schema with app code"
# The dump predates columns the app writes (user_id, rejection_reason) and
# leaves legacy path/size as NOT NULL without defaults, which breaks inserts
# under strict mode.
$MYSQL "$DB" -e "ALTER TABLE uploaded_files
  ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER user_case_id,
  ADD COLUMN rejection_reason TEXT NULL AFTER review_notes,
  MODIFY path VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY size BIGINT UNSIGNED NOT NULL DEFAULT 0;"

echo "==> Applying migrations added since the dump was taken"
# The dump lags the repo's migrations (timeline enum additions, the document
# extraction tables). Without this, a reset silently drops them and the
# extraction tests fail on missing tables.
"$PHP_BIN" artisan migrate --force

echo "==> Cloning schema into \`$TEST_DB\` for phpunit"
$MYSQL -e "DROP DATABASE IF EXISTS \`$TEST_DB\`; CREATE DATABASE \`$TEST_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysqldump -h 127.0.0.1 -P 3306 -u root --set-gtid-purged=OFF "$DB" | $MYSQL "$TEST_DB"

echo "==> Running seeders"
"$PHP_BIN" artisan db:seed --force

echo "==> Done. Demo DB ready."
