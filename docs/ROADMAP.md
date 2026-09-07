# Rapid Rebuilt — Build Roadmap (v4, two tracks)

*v4 (3 Aug 2026): adds S2.5 and the multi-state note, both from the demo call.
Transcript: `docs/calls/2026-08-03-demo-call-transcript.txt`.*

Two tracks that run in parallel after S1: the **Software** track builds the machine, the
**Marketing** track fills it. Payment: 50% of a stage up front, 50% on demo of that stage.

Every technology choice below was verified against active 2026 production codebases
(see docs/RESEARCH.md for the evidence log). Nothing legacy, nothing deprecated.

---

# TRACK S — THE SOFTWARE

## S1 — Make the Portal Real (Weeks 1–2) — $3,500

The app today crashes on document upload and can't generate forms. S1 turns it into a live,
working portal on a real domain, plus two cheap wow-features that demo well.

**First half: working demo on the current stack**
1. Fix document storage so uploads, staff review, and approvals actually work
2. Clean the database (strip leftover 2021 app tables, seed real pricing/settings)
3. Magic-link login: every case email contains a one-time expiring link straight into the
   case (Laravel signed routes — native, no extra moving parts)
4. Public "Rebuilt Tracker": no-login tokenized link per case showing
   Docs Received → Scheduled → Inspected → Forms Filed → Title Issued.
   The case timeline already logs all of this; customers just can't see it yet.

**Second half: modernize and ship (after the local demo works)**
5. Runtime upgrade: PHP 8.1/Laravel 10 are EOL. Bump to PHP 8.3+ and Laravel 12
   (current stable) with the demo as the regression baseline. Doing it later costs triple.
6. Deploy to production: domain, SSL, automated backups, error monitoring

**Demo:** customer signs up on a real URL, uploads paperwork, books, pays, and gets a
tracker link for their phone. Staff sees every case in admin.

## S2 — The Edge: AI Auto-Fill + Official Forms (Weeks 3–6) — $9,500

The part nobody else in Florida has. Customer uploads paperwork, state forms fill themselves.

1. AI document reading (vision LLM via Prism, the Laravel-standard AI layer — provider-
   swappable between Anthropic/OpenAI/Google so we're never locked into one AI vendor):
   salvage title, bill of sale, ID, insurance, receipts → structured data. Pennies per case.
2. Human confirmation screen: staff sees what the AI read side by side with the document,
   confirms or corrects. Nothing goes on a state form unverified.
3. Official Florida HSMV PDFs form-filled programmatically (php-pdftk — same technique the
   UK's NHS uses to fill official government forms): 82040, 84490, 84491, 82050, 82053
   AND 82042 (VIN/odometer verification — the old build forgot it entirely).
   Real state PDFs, not HTML lookalikes.
4. Document readiness meter: "4 of 6 documents approved, receipt missing donor VIN,"
   auto-nudges on rejection. Green light before inspection day = no wasted appointments.
5. Guided mobile capture: "photograph your title" opens the phone's rear camera directly
   (browser-native, zero app install), flags blur/cut-off shots before submission.
6. One-click day-of packet: every form printed, labeled, ready for wet signatures.

**Demo:** phone photo of a salvage title goes in, a filled 82040 comes out.

### Form pack — verified 3 Aug

All six official PDFs are in `docs/forms/` and all six are genuinely fillable, confirmed
with `pdftk dump_data_fields`. This was the main technical risk in S2 and it is now retired.

| Form | What it actually is | Fields |
|---|---|---|
| 82040 | Application for Certificate of Title | 215 |
| 82042 | VIN and Odometer Verification | 38 |
| 82050 | Notice of Sale and/or Bill of Sale | 40 |
| 82053 | Power of Attorney for a Motor Vehicle | 35 |
| 84490 | Statement of Builder | 134 |
| 84491 | Statement of Builder — Additional Sheet | 122 |

The previous build's **inspector view** (`resources/views/inspector/inspection/documents.blade.php`)
labelled 84490 "Rebuilt Motor Vehicle Inspection", 84491 "Major Component Parts Form",
82050 "Statement of Builder" and 82053 "Odometer Disclosure Statement". All four were
wrong. **Fixed on 17 Aug** (`a6b7134`): the view no longer carries its own labels and
reads `PDFGeneratorService::PACKAGE_FORMS` instead, so the packet manifest and the screen
can never disagree again. The stale "Also found" note in `docs/82050-coverage.md`
describes this same, now-fixed bug.

`PDFGeneratorService` never carried those labels. Its own `PACKAGE_FORMS` labels were
wrong for 84490 and 84491 at first and both are now corrected: 84490 to "Statement of
Builder" on 11 Aug, 84491 from "Rebuilt Vehicle Inspection Application" to "Statement of
Builder, Additional Sheet" on 16 Aug. The 82053's "Power of Attorney" was verified against
the PDF's printed title on 16 Aug and was already correct.

**Weight:** the 82040 has separate `Weight` and `GVW` fields. Client confirmed weight comes
from what is printed on the title — so it is AI-extracted from the salvage title, never
auto-filled from the VIN decoder's GVWR.

## S2.5 — Inspection Log & DMV Access (Weeks 7–9) — $4,500

**Added after the 3 Aug demo.** He described this himself: *"we have what's called a log…
we have to share it with the DMV or give them access to it… they could see when the
inspection was conducted, who conducted the inspection, and they want to see the pictures."*

This is the compliance record that makes the DMV/Tallahassee conversation possible. It was
previously buried as a footnote in S3; it is his second-strongest ask and gets its own stage.

1. **Inspector field capture (mobile):** photos taken on-site, geotagged and timestamped at
   the moment of capture. The `inspection_photos` table and `InspectionService::addPhoto()`
   already exist with latitude/longitude/taken_at/device_info columns and 24 defined photo
   types — there is no web UI wired to them. This builds that UI.
2. **The inspection log itself:** one immutable record per inspection — date/time conducted,
   which inspector conducted it, checklist results, and every photo, all in one view.
3. **VIN-keyed lookup:** find any inspection by VIN, not just by case. Explicitly requested
   (*"attach the inspection according to the vin number"*).
4. **DMV read-only access:** a fourth role, or a tokenized verification link per inspection,
   that exposes the log and nothing else — no revenue, no other customers' cases. Same
   pattern as the public tracker already shipped in S1.
5. **Excel/CSV export:** the log as a working spreadsheet (*"maybe create a working excel
   file"*), per-inspection or date-ranged for bulk state reporting.
6. **Cloud retention:** logs and photos held in private storage with a defined retention
   window, so a request from the state months later can still be answered.

**Demo:** hand over a VIN, get back a timestamped, geotagged, photographed inspection record
the state can verify without a login.

**Dependency:** ships after S2 — the packet is stronger when generated forms and the log
are one record. Can run before S2 if the state conversation moves first.

---

## Multi-state expansion (quoted per state, not bundled)

Raised in the demo: *"it's just for Florida right now… we will need all the states."*

S2 builds the extraction engine and the Florida HSMV form pack. The engine is
state-agnostic; the forms are not. Every additional state is its own research pass, its own
PDF templates, and its own field-mapping and validation rules.

**Roughly $2,000–$3,000 per state pack** once the Florida engine exists, quoted individually
after confirming which states he actually gets volume from. Do not promise "all 50" — that
is a multi-year product, not a feature, and pricing it as one number destroys the margin.

The honest framing for him: Florida first, prove it, then add states in the order his
revenue justifies.

---

## S3 — Scale & Licensing (future, separate conversation)

- Dealer bulk portal: CSV/VIN-list bulk case creation, volume pricing, fleet dashboard
  (the `dealer` user type already exists in the code, unused)
- White-label / multi-tenant for licensing to other rebuilt-title shops
- Additional state form packs (see multi-state note above)
- E-signature on generated forms: self-hosted Documenso (13k-star open source, no
  per-envelope fees, title documents never leave our server). Day-of flow stays
  print + wet-sign; e-sign only matters when pitching Tallahassee on going paperless.
- The Tallahassee pitch: official forms + e-signature + QR certificates + audit trail.
  Most of the demo builds itself in S1–S2.5 + M2 — S2.5 is now the centerpiece.
- Live-updating dashboards (websockets) if case volume justifies it

---

# TRACK M — THE MARKETING

Starts as soon as S1 is live, runs in parallel with S2. By the time the AI demo is ready,
leads are already arriving.

## M1 — Foundation (Weeks 3–5) — $3,500

1. Marketing site, education-first: "What is a rebuilt title?", "Your totaled car isn't
   dead", "Boats, trailers, golf carts, semis: anything with a title qualifies"
2. Public instant-quote + eligibility widget: visitor enters VIN → auto-decode → 3 questions
   → "Yes, your vehicle qualifies. Here's your itemized price" → books straight into the
   portal. (The VIN decoder already exists internally; this exposes it as the funnel top.)
3. SEO targeting "rebuilt title inspection Florida / Tampa / Orlando"
4. Google Business profile tune-up

## M2 — Outreach Engine (Weeks 6–8) — $2,500

1. Two-way SMS: appointment reminders with "reply 1 to confirm, 2 to reschedule," status
   pings at every case step, text-to-pay links (Twilio — the old build has a literal
   "TODO: Implement Twilio" where this belongs)
2. Automated review ask: the moment a case passes, customer gets a text with the Google
   review link
3. QR-verified inspection certificate: passed inspections issue a certificate anyone can
   scan to verify (same QR-verification technique EU governments mandate for receipts).
   Fights resale stigma; every scan is free marketing.
4. Dealer outreach: import the Florida dealership list, branded email campaign, open/click
   tracking, follow-up queue
5. Social kit: 30-day IG/FB content calendar + reusable branded templates

## M3 — Ongoing Growth (rolls into the monthly)

Covered by the $950/mo below: campaigns keep sending, content calendar refreshed monthly,
review pipeline monitored, monthly report delivered.

---

## Ongoing — $950/month (starts after M2)

Not a fee for nothing. Every month, specifically:

| Item | What it is |
|---|---|
| Hosting + infrastructure | Server, domain, SSL, daily backups, uptime monitoring |
| AI usage | Document AI reads covered up to 500 docs/mo |
| SMS usage | Reminder/status texts covered up to 1,000 messages/mo |
| Security + updates | Framework security patches, dependency updates, Stripe API upkeep |
| Fixes | Anything broken gets fixed, no hourly billing |
| Campaign ops | Dealer campaigns keep sending, deliverability monitoring, list hygiene |
| Content refresh | Next month's social calendar + template updates |
| Reporting | Monthly one-pager: cases, portal revenue, traffic, campaign results |
| Priority line | Portal down on a Saturday gets handled Saturday |

Not included (quoted separately): new features, ad spend (goes straight to Google/Meta),
content shoots, S3 licensing work.

---

## Money summary

| Stage | Price | Terms |
|---|---|---|
| S1 — Portal live | $3,500 | $1,750 to start, $1,750 on live demo |
| S2 — AI + official forms | $9,500 | $4,750 to start, $4,750 on demo |
| S2.5 — Inspection log + DMV access | $4,500 | $2,250 to start, $2,250 on demo |
| M1 — Marketing foundation | $3,500 | $1,750 to start, $1,750 on launch |
| M2 — Outreach engine | $2,500 | $1,250 to start, $1,250 on launch |
| **Build total** | **$23,500** | vs. $55k–$80k from an agency |
| Ongoing | $950/mo | starts after M2, cancel anytime with 30 days notice |
| Extra state packs | $2,000–$3,000 each | quoted per state, after Florida is proven |

S2.5 is new as of the 3 Aug demo. Present it as a separate decision, not as a
$4,500 increase to a number he already agreed to.
