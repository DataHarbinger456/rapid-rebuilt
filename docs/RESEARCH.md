# Tech Research Log — MCP code-search pass (Jul 30, 2026)

Goal: verify every roadmap feature has a current (2026) implementation pattern grounded in
real, active, public code. Reject anything stale or superseded.

## Adopted patterns (repo/file-anchored)

| Feature | Pattern | Evidence (repo · file · freshness) |
|---|---|---|
| AI doc extraction | **prism-php/prism** `Prism::structured()->withSchema(...)` + `Image::fromLocalPath()` — Laravel-native multi-provider LLM layer (Anthropic/OpenAI/Gemini swappable) | prism-php/prism ★2.4k (Mar 2026); production use: karlomikus/bar-assistant ★1.0k `GenerateController.php` (Jul 2026); kantorge/yaffa ★104 `OcrService.php` + `AiStepGateway.php` (Jul 26, 2026) — full doc pipeline: resize image → Prism vision → structured schema → human review, with `AiDocument` status model. unopim ★9.9k compresses images before sending (Jul 30, 2026) |
| Doc-pipeline data model | `AiDocument` (status enum, hasMany files, linked to source) + separate extraction step | kantorge/yaffa `app/Models/AiDocument.php` — mirrors our UploadedFile → extraction → confirm flow |
| HSMV PDF filling | **mikehaertl/php-pdftk** `$pdf->fillForm($fields)->needAppearances()->saveAs()` on the official state PDF | mikehaertl/php-pdftk ★1.0k (updated Jul 21, 2026); production analog: AppertaFoundation/openeyes (NHS EMR, Mar 2026) `OphCoCvi_Manager.php` fills official UK government CVI forms this exact way |
| E-signature | **Documenso** REST API (`DOCUMENSO_API_TOKEN`, documents + templates endpoints), self-hosted | documenso/documenso ★13k (Jul 30, 2026); integration exemplar: YosemiteCrew/Yosemite-Crew ★2.1k `documenso.service.ts` (Jul 30, 2026) — thin REST service class, transferable to PHP |
| Magic-link login + public tracker links | Native `URL::temporarySignedRoute()` — no package needed | Canonical: laravel/breeze ★3.0k stubs (Jun 2026); production deep-links: Cybrarist/Discount-Bandit ★696 `ProductDiscounted.php` (Jun 2026) signs expiring notification links incl. snooze actions |
| SMS + two-way replies | **laravel-notification-channels/twilio** for outbound; `Twilio\TwiML\MessagingResponse` webhook for inbound "reply 1 to confirm" | fleetbase/fleetbase ★1.9k (Jul 27, 2026) ships it in production (`^3.3`); twilio/twilio-php ★1.6k active (Jul 30, 2026); webhook pattern: GoogleCloudPlatform/php-docs-samples `twilio/app.php` |
| QR-verified certificate | **endroid/qr-code** `Builder` → QR on PDF cert → public signed verify route | Used for *government-mandated* QR in nticaric/fiskalizacija (Croatian fiscal receipts, Jul 2026) and Barnetik/tbai-php-lib (Basque TicketBAI, Jul 20, 2026) — legally-verifiable-QR precedent, exactly our DMV-credibility angle |
| Mobile doc capture | `<input type="file" accept="image/*" capture="environment">` — browser-native, zero install | Live in 2026 apps: cubewhy/skid-homework ★1.5k (Apr 2026), mreichhoff/HanziGraph (Jun 2026), LikasLens PWA (Jul 2026) |
| Framework target | **Laravel 12** is current stable (`"laravel/framework": "^12"` across active repos) | solidtime-io/solidtime ★8.6k, Discount-Bandit (both 2026). Upgrade path: 10 → 11 → 12 |

## Rejected (old / superseded)

| Candidate | Why rejected |
|---|---|
| FPDM (codeshell/fpdm) for PDF filling | Last commit Dec 2023, contains literal PHP4-era hacks in source. php-pdftk wins |
| barryvdh/laravel-dompdf HTML→PDF for state forms | Wrong approach entirely — lookalike forms vs. filling official HSMV PDFs. Keep dompdf only for internal reports if needed |
| spatie/laravel-login-link | Looks right, is wrong: **dev-environment-only by design** (env whitelist in config). Customer magic links = native signed routes instead |
| Tesseract/legacy OCR | No serious 2026 Laravel doc-extraction codebase found using it; all active ones (yaffa, unopim, bar-assistant) went vision-LLM via Prism |
| Direct provider SDK lock-in (openai-php only) | Prism abstracts Anthropic/OpenAI/Gemini/Groq behind one API — provider-swap freedom for AI costs |
| aimeos/prisma (★175) | Younger clone of prism-php with smaller ecosystem; prism-php is the standard |
| Laravel Reverb websockets for live status | Real but requires PHP 8.2+/replatforming for marginal win over the signed tracker page; revisit in Phase S3 |
| Old Twilio Laravel tutorials (2023 repos) | Package itself is current (fleetbase proves it); tutorials stale, ignore |

## HSMV form fillability check (Jul 30, 2026 — verified locally)

Downloaded from flhsmv.gov (`https://www.flhsmv.gov/pdf/forms/<num>.pdf`), saved in
`docs/forms/`, inspected with `pdftk dump_data_fields`:

| Form | Verdict | Fields |
|---|---|---|
| 82040 (title application) | **Fillable AcroForm** | 215 fields: 119 Text, 87 Button (checkboxes), 9 Signature. Sample names: `Original`, `Transfer`, `Yes In office` |
| 84490 (rebuilt inspection) | **Fillable AcroForm** | 134 fields: 67 Text, 61 Button, 6 Signature. Sample names: `REBUILT`, `ASPT`, `KIT CAR`, `Motor Vehicle` |
| 82042 (VIN/odometer verify) | **Fillable AcroForm** | 35 fields: 27 Text, 8 Button, no Signature fields. Sample names: `Vehicle Identification Number`, `Year`, `Make` |

**S2 consequence:** the php-pdftk `fillForm()` approach is CONFIRMED viable — no overlay/
stamping fallback needed for these three. Caveats: field names are messy/ambiguous
(82040's `No`, `Yes Mailed`), so S2 needs a per-form field-mapping pass with filled visual
proofs; checkboxes are Button fields needing their export values (`dump_data_fields_utf8`
shows `FieldStateOption`); 84490/82040 Signature fields stay empty for wet signing.
Remaining forms (84491, 82050, 82053) must get the same check during S2.

## Query log (compact)

Prism::structured() ✓ · new FPDM( ✗stale · mikehaertl\pdftk\Pdf ✓ · Image::fromLocalPath ✓ ·
AiDocument@yaffa ✓ · Spatie\LoginLink ✗dev-only · temporarySignedRoute ✓ · HSMV 82040 ∅ (no
public FL fill-maps — field map must be built from the real PDFs, expected) · TwilioSmsMessage
~(package current, exemplars stale) · ->fillForm( ~(Filament noise) · needAppearances() ✓
(openeyes gov-form analog) · DOCUMENSO_API ✓ · Endroid\QrCode\Builder ✓ (gov QR precedents) ·
laravel/framework ^12 ✓ · Route::get('/track/ ~(weak; signed-route pattern covers) ·
MessagingResponse ✓ · discoverRepos "laravel pdf form fill" / "document collection client
portal PHP" ∅ (too specific, pivoted to literal anchors)

Regional coverage: US/EU (spatie BE, mikehaertl DE, documenso DE/US), UK gov (openeyes NHS),
Croatia (fiskalizacija), Basque Spain (tbai), India (unopim/Webkul), Iran (mirzabot), China
(ThinkAdmin), Hungary (yaffa). Saturation: final batches returned repeats/generic only.
