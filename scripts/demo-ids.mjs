// Resolve demo-case IDs from the local DB so reseeds (which shift
// auto-increment IDs) don't break the flow scripts.
import { execSync } from 'node:child_process';

const MYSQL = `mysql -h 127.0.0.1 -P 3306 -u root admin-rapid -N -e`;

function q(sql) {
  const out = execSync(`${MYSQL} "${sql}"`).toString().trim();
  if (!out) throw new Error(`demo-ids: no row for: ${sql}`);
  return out;
}

const CUSTOMER = `(SELECT id FROM users WHERE email='mike@example.com')`;

// Newest case per status = the ones created by the demo seeder.
const byStatus = (status) =>
  q(`SELECT id FROM user_cases WHERE user_id=${CUSTOMER} AND status='${status}' ORDER BY id DESC LIMIT 1`);

export const CASE_PENDING_DOCS = q(
  `SELECT uf.user_case_id FROM uploaded_files uf
   JOIN user_cases c ON c.id = uf.user_case_id
   WHERE c.user_id=${CUSTOMER} AND uf.status='rejected'
   ORDER BY uf.user_case_id DESC LIMIT 1`
);
export const CASE_SCHEDULED = byStatus('scheduled');
export const CASE_DOCS_COMPLETE = byStatus('documents_complete');
export const CASE_PENDING_PAYMENT = byStatus('pending_payment');
