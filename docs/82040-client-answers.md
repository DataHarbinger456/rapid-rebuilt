# HSMV 82040 — Client Answers

Genesis Nicholson's answers to the eight questions in
`docs/82040-client-questions-gdoc.html`, received 13 Aug 2026 11:38.

**Ebe has not answered.** The form deliberately asked the two of them to answer
independently, on the grounds that a disagreement is the useful signal. Nothing below
has a second opinion against it yet, so treat FIELD 01, 02 and 06 as one person's
reading rather than a settled company position.

---

## Verbatim

```
FIELD 01  Mailing address vs. home address
  Something else
  notes: Addersse will be used should would be driver license address
FIELD 02  Who certifies the VIN?
  It varies per inspection
  rule: Out of state vessels only require this confirmation
FIELD 03  Printing the certificate of title
  Leave it blank for the customer to tick on the day
FIELD 04  GVW
  Leave GVW blank
FIELD 05  Odometer: 5 or 6 digits
  Have inspectors record it on site instead
FIELD 06  Dealer section
  Rapid Rebuilt fills this in
  taxreg: Per dealer information
  dealerlic: Per dealer information
FIELD 07  The unlabeled box
  Don't know, leave it blank
FIELD 08  The two Full Name of Applicant boxes
  Don't know, leave both blank

Anything else: Not at this time
```

---

## What each answer changed

| | Answer | Status |
|---|---|---|
| 01 | Driver licence address, in both blocks | **Done** — and it exposed a bug |
| 02 | "Varies per inspection" | **Still blocked** — answers *when*, not *who* |
| 03 | Leave blank | **Settled** — already the behaviour |
| 04 | Leave GVW blank | **Settled** — already the behaviour |
| 05 | Inspectors record it on site | **Needs building** — changes current behaviour |
| 06 | Rapid Rebuilt fills it | **Still blocked** — no numbers supplied |
| 07 | Leave blank | **Settled** — already the behaviour |
| 08 | Leave both blank | **Settled** — already the behaviour |

### 01 — Driver licence address, both blocks. Done, and it found a bug.

The answer is that the address printed on the form is the one on the owner's driver
licence, and it goes in both the mailing and the residential block.

Measuring the widget positions to apply that showed the form has two address rows:

```
row top=232   Owners Mailing Address | Owner's City | Owner's State | Owner's Zip Code
row top=255   Owner's Residential Street Address | Owner’s Residential City | State | Zip
```

We had been filling the **street from the residential row** and the **city, state and zip
from the mailing row**. A printed form therefore carried a street with no city on one
line, and a city with no street on the next: neither row was a complete address, on a
document signed under penalty of perjury. Both rows are now filled from the same keys,
which is what was asked for and repairs the split.

**One gap this opens.** Nothing in the app asks for the *driver licence* address
specifically; intake asks for "Address". So we now print an address the customer typed at
signup into a box the state expects to match their licence. Making that true needs either
a label change at intake or a confirmation step, and that is a product decision, not a
mapping one.

### 02 — Still blocked. The question was who; the answer is when.

> It varies per inspection. Out of state vessels only require this confirmation.

Read as *vehicles* rather than vessels, this is a useful and probably correct rule, and it
matches the 82042's own instructions, which say that form is for vehicles not currently
titled in Florida. It tells us **when** Section 8 applies.

It does not say **which of the five authorities** signs it — notary, law enforcement,
Florida dealer, FLHSMV or tax collector — and the licence/badge number field on the form
came back empty. Section 8 of the 82040 and the whole of Part B of the 82042 stay
unmapped, which is 12 fields on the 82042 alone.

Worth confirming "vessels" was a slip for "vehicles" before relying on the rule at all.

### 05 — Needs building, and it changes three forms.

Today `is_six_digit_odometer` is derived from the model year: true for 1995 onward, and
the 5-digit box is never ticked automatically on any form. The answer is that inspectors
should record what the odometer physically shows.

That means an inspector-entered value replacing a derived one, which touches:

- a new field on the inspection, and somewhere for the inspector to enter it
- `prepareFormData()`, where the model-year rule currently lives
- three field maps that already consume the key: 82040, 82042 and 82050

It also lets the 5-digit box be ticked for the first time, which no form does today.

### 06 — Still blocked. "Per dealer information" is not a number.

The answer is that Rapid Rebuilt completes the dealer section, but both number fields came
back as "Per dealer information" rather than digits. `settings.prvip_license_number` and
`settings.tax_id` both exist and are **empty**, so there is nothing to fall back on.

Section 7 needs the actual sales tax registration number and dealer licence number before
any of it can be filled.

### 03, 04, 07, 08 — Settled, no code change.

All four confirm what the code already does, and all four were previously marked ⚠ on the
guess that they might change. They are now decisions rather than assumptions:

- **03** the title-printing boxes stay blank for the customer to tick on the day
- **04** GVW stays blank; Weight continues to come off the title
- **07** the unlabelled box beside Passenger stays blank
- **08** both "Full Name of Applicant" boxes stay blank

---

## Still outstanding

1. **Ebe's answers.** Nothing here has been cross-checked.
2. **Who signs Section 8** — the actual question from FIELD 02, still unanswered.
3. **The dealer numbers** — the actual digits for FIELD 06.
4. **"Vessels" or "vehicles"** in the FIELD 02 rule.
5. **Whether intake should ask for the driver licence address**, now that we print it as
   though it is one.
