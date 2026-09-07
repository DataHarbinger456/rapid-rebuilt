# CLAUDE.md

Rapid Rebuilt — Florida rebuilt-title portal. Customers submit a case, upload documents,
pay, book an inspection; staff review and generate the DMV packet.

## Stack

PHP ^8.3 (8.3.33) · Laravel 12.64 · MySQL · Blade + Bootstrap theme, no SPA ·
Vite 4 + Sass (`npm run dev` / `npm run build`). Key deps: `prism-php/prism` (AI document
extraction), `mikehaertl/php-pdftk` (fills the official HSMV PDFs), `stripe/stripe-php`,
`laravel/sanctum`, `sentry/sentry-laravel`, `spatie/laravel-backup`. Playwright drives
browser checks (`scripts/`, gitignored).

## Tests

`php artisan test` runs everything; add a path for one file or `--filter=name` for one
test. Suites: `tests/Unit`, `tests/Feature`. Tests run against a **real MySQL database**
(`admin_rapid_test`), not sqlite, and use `DatabaseTransactions` — not `RefreshDatabase`,
because the schema comes from the SQL dump. Mail uses the `array` transport; assert on
`app('mailer')->getSymfonyTransport()->messages()`. `Mail::fake()` is blind to this
codebase's closure-based `Mail::send`.

## Domain vocabulary

Use these names verbatim in code, tests, and conversation.

**UserCase** — one customer's rebuilt-title job, identified by `case_number`. Owns the
vehicle, components, documents, appointment, inspection, payments, and timeline.

**Status** — where a case sits in the pipeline. 18 values (`UserCase::STATUSES`):
`draft → submitted → pending_payment → paid → pending_documents → documents_complete →
pending_scheduling → scheduled → inspection_in_progress → pending_review → passed |
failed → generating_documents → ready_for_dmv → submitted_to_dmv → title_issued →
completed`, plus `cancelled`. Legal moves live in `UserCase::canTransitionTo()`; never
write `status` directly, call `updateStatus()`.

**Vehicle** — the car under a case. One per case, VIN-keyed.

**Component** — a replaced major part with its condition and donor VIN. Drives the 84491.

**UploadedFile** — a customer document (title, bill of sale, licence, receipts). What a
case needs comes from `DocumentRequirements`; staff approve or reject each one.

**UserFormScheduling** — the inspection appointment. There is no `Appointment` model.
`start`/`end` are the slot; `location_address` is set **only** for a mobile inspection —
an office booking has none, so guard on that field, not on `full_address`.

**Inspection** — the inspector's on-site checklist and photos. `result` is `pending |
in_progress | passed | failed | incomplete`, distinct from case status.

**CaseTimeline** — append-only audit trail of what happened to a case.

**HSMV forms** — the six official Florida forms. Use the number and these titles:

| Form | Title | State |
|---|---|---|
| 82040 | Application for Certificate of Title | mapped |
| 82042 | VIN and Odometer Verification | mapped |
| 82050 | Notice of Sale and/or Bill of Sale | mapped |
| 82053 | Power of Attorney for a Motor Vehicle | blocked on client answers |
| 84490 | Statement of Builder | blocked on client answers |
| 84491 | Statement of Builder — Additional Sheet | blocked on client answers |

## Where things live

- **Notifications** — `app/Services/NotificationService.php`. Every in-app notification
  and outbound email funnels through it. `sendEmail()` honours the customer's
  `notifications.email` preference and **swallows all failures into `Log::error`**, so a
  broken template vanishes silently; `NotificationEmailCoverageTest` catches that.
- **Email templates** — `resources/views/emails/`, `mail::` markdown components. That
  namespace is registered in `AppServiceProvider` because closure-based `Mail::send`
  doesn't register it itself.
- **Settings** — `app/Models/Setting.php`, seeded by `SettingSeeder`.
- **PDF generation** — `app/Services/PDFGeneratorService.php`, field maps in
  `config/hsmv_forms.php`, blank templates in `docs/forms/`, filled via pdftk
  (`app/Support/PdftkBinary.php`). Per-form audits in `docs/*-coverage.md`.
- **AI extraction** — `app/Services/Extraction/`, configured by `config/extraction.php`.

## Setting keys

`business_*` is the source of truth for company info: `business_name`, `business_email`,
`business_address`, `business_phone`, `business_hours`. `Setting::companyName()` and
`companyEmail()` read those keys despite their own names. **Do not reintroduce
`company_*` keys** — a parallel `company_name`/`company_email` block was seeded once but
never written to, so PDFs and email `From` headers quietly served hardcoded fallbacks in
production instead of configured values. Read with `Setting::get()` (cached 1h), write
with `Setting::set()` (busts the cache). Groups: `pricing`, `notifications`, `business`,
`scheduling`.

## The rule

**No change ships without a test that fails first.** Write the test, run it, watch it
fail *for the reason you expect*, then write the fix and watch it pass. A test that was
never red proves nothing. Not ceremony here: this codebase swallows exceptions in its
most important path, so a bug can look fine in every manual click-through and still be
broken — every notification defect found so far was invisible until a test named it.

Never make a failing check pass by weakening it: no deleted assertions, no relaxed
matchers, no skipped tests. Fix the code or surface the conflict.
