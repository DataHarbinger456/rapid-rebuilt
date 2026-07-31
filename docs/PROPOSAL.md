# Rapid Rebuilt — Portal + Marketing Proposal (Working Doc)

## 1. What the app he sent actually is

Someone already tried to build his exact vision — this is attempt #1, and it stalled half-done.

**The business:** Florida rebuilt-title inspections. A car gets wrecked → title branded "salvage" →
owner repairs it → state requires a certified inspection + a stack of HSMV forms to re-brand it
"rebuilt" so it's legal to drive/sell. Rapid Rebuilt LLC runs those inspections. Applies to anything
titled: cars, boats, trailers, golf carts, semis.

**What the app was trying to pull off (the intended flow):**

1. Customer creates an account → starts a "case" for their vehicle
2. Enters VIN → app auto-decodes it (NHTSA API) → year/make/model/engine prefilled
3. Uploads the required docs: salvage title, bill of sale, ID, insurance, damage photos,
   repair photos, component receipts
4. Staff reviews/approves each doc; customer schedules the inspection and pays online (Stripe)
5. Inspector runs the physical inspection on a checklist (photos, VIN verify, components)
6. App generates the official Florida forms (HSMV 82040, 84490, 84491, 82050, 82053) pre-filled —
   customer walks in, papers are printed, wet-sign, inspect, done
7. Admin sees pipeline, reports, revenue; everything logged in a case timeline

Three portals (Admin / Customer / Inspector) + an API. The architecture is legitimately good.

**What's actually broken / never built:**

| Piece | Status |
|---|---|
| Case management, roles, scheduling, Stripe, VIN decode | Built, mostly solid |
| Document upload | **Crashes** — writes to a "private" storage disk that was never configured |
| DMV form generation | **Fatal error** — PDF library never installed, form templates don't exist (0 files) |
| "Docs auto-fill the forms" (the headline promise) | **Never built** — no OCR/AI anywhere in the codebase |
| Payments history, messaging | Placeholder routes ("implement later") |
| Database | New schema tangled with a legacy 2021 app's tables in one dump |

**Bottom line:** he was sold (or built) the skeleton of the right idea. The two features that ARE
the pitch — upload docs → forms fill themselves → walk in and sign — are vapor. That's the gap we close.

## 2. What he said he wants (from the calls)

1. **The portal** — "upload documentation prior to the inspection… it's about creating efficiency."
   Docs in ahead of time, forms pre-filled, day-of is just print → wet signature → envelopes → inspect.
   Fewer errors, faster turnaround.
2. **Marketing** — "corner the market of rebuilt title inspection." Public education (people don't
   know a totaled car can be retitled), Instagram/Facebook push, content sessions, and his list of
   **every FL dealership** (emails/phones — public data) for direct outreach.
3. **The moonshot** — license the portal to other rebuilt-title shops or pitch the DMV/Tallahassee on
   e-signature + electronic document retention (they currently must hold paper 5 years). Perpetual
   licensing money.

Phases 1–3 below deliver #1 and #2. #3 is the vision we architect toward but don't charge for yet.

## 3. Phased build

### Phase 1 — Make it run (Weeks 1–2)
Fix the broken plumbing so what exists actually works end-to-end, then put it live.

- Fix private storage → document upload/review works
- Fix config, seed settings/pricing, clean the legacy tables out of the DB
- Deploy: domain, SSL, backups, error monitoring
- Live walkthrough: create case → upload docs → schedule → pay → inspector checklist

**Deliverable he can see:** a real customer can sign up, upload, book, and pay on a live URL.

### Phase 2 — The magic: ingestion + official forms (Weeks 3–6)
The actual edge he described on the call.

- AI document extraction: uploaded title/bill of sale/ID/receipts → structured data
  (owner, VIN, dates, amounts) with a human "confirm/correct" screen — no blind trust
- Extracted data + VIN decode auto-fill the case
- Official HSMV PDFs (82040, 84490, 84491, 82050, 82053) filled programmatically —
  the real state forms, not homemade lookalikes (matters for the DMV angle later)
- One-click "day-of packet": every form printed, labeled, ready for wet signature
- Component receipts → auto-populate the major-components form

**Deliverable:** upload a phone photo of a salvage title → watch the 82040 fill itself.

### Phase 3 — Corner the market (Weeks 7–10)
- Public marketing site: education-first ("What is a rebuilt title?", "Totaled ≠ dead"),
  SEO targeting "rebuilt title inspection Florida/Tampa", booking funnel straight into the portal
- Google Business + review pipeline (post-inspection review ask, automated)
- Dealer outreach engine: import his FL dealership list, branded email campaigns,
  open/click tracking, simple follow-up queue
- Social kit: content calendar + templates for the IG/FB education push

**Deliverable:** traffic + dealer pipeline flowing into the same portal.

### Phase 4 — The licensing play (later, separate deal)
Multi-tenant/white-label, e-signature, DMV pitch materials. Architected-for now, sold later.

## 4. Pricing

**Market reality (what an outside shop quotes in 2026):** custom customer portals run $15k–$180k+;
US-rate B2B portal builds $30k–$150k; mid-range portals with integrations/AI $40k–$150k. This scope
(3-role portal, payments, AI extraction, state-form automation, marketing site) from a US agency:
**$60k–$90k**, and 3–5 months. Even a cheap offshore team lands ~$20k–$35k with heavy management risk.

| Phase | Market (US shop) | Friend price |
|---|---|---|
| 1 — Make it run | $8k–$12k | **$3,500** |
| 2 — Ingestion + forms | $30k–$45k | **$9,500** |
| 3 — Marketing engine | $15k–$25k | **$6,000** |
| **Build total** | **$55k–$80k** | **$19,000** |
| Ongoing: hosting, AI costs, upkeep, campaign support | $2k–$5k/mo typical | **$750–$1,500/mo** (scope TBD) |

Friend price ≈ 65–70% off market. That's the discount — visible, named, and finite.

**Payment structure (non-negotiable):**
- 50% of each phase up front, 50% on demo of that phase's deliverable
- Phase 1 starts when the $1,750 lands. Nothing starts on a handshake.
- Each phase ends with something he can touch — he's never paying on faith twice.

## 5. Pitch angles that land with HIM specifically

- He said it himself: "practically bankrupt… $120k+ in." Phases mean small checks, visible wins,
  no more big-bang bets.
- The app he has = proof he already paid for vapor once. Not trash-talk — receipts:
  "uploads crash, forms don't generate, the auto-fill was never built. I checked."
- His moonshot (license it, DMV deal) only works if the forms output is the *official* state PDFs
  and the data handling is clean — which is exactly what Phase 2 builds.
- Marketing without the portal = leads into a leaky bucket. Portal without marketing = empty software.
  He needs both; that's why it's phased in this order.
