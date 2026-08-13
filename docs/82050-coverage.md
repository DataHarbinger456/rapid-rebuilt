# HSMV 82050 — Field Coverage Audit

Every one of the 40 fillable fields in `docs/forms/82050.pdf`, classified against what the
system can supply today. Field names come from
`pdftk docs/forms/82050.pdf dump_data_fields_utf8`, dumped verbatim to
`docs/forms/82050-fields.txt`; copy the canonical name from there when writing a mapping.

The form is **Notice of Sale and/or Bill of Sale for a Motor Vehicle, Mobile Home,
Off-Highway Vehicle or Vessel**, one page.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

> **Updated 11 Aug 2026.** The 20 clean class B fields are now mapped, so class A is 20
> and B is down to the three ⚠ fields; the counts below have moved accordingly. Everything
> still marked ⚠ or D is untouched and still waiting on the client.

There was no entry for this form in `config/hsmv_forms.php` when this audit was written, so
class A started empty. `generateHSMV82050()` already existed and reached `fillForm()`,
where it threw the "still in development" `RuntimeException` for want of a field map; with
the map in place it now produces a filled form.

---

## Read this first: the field names are auto-generated and misleading

Acrobat named most of these fields after whatever text sat nearest when the form was
authored, not after what the box is for. Twelve of the 40 have names that actively lie:

| Field name in the PDF | What the box actually is |
|---|---|
| `Iwe do hereby sell or have sold and delivered the above described motor vehicle mobile home` | Certificate of Title Number |
| `2` | Date of Sale |
| `Odometer Disclosure Statement Required For a Motor Vehicle` | Selling price |
| `Text1` | Odometer hundred-thousands digit |
| `Federal and State law requires that you state the mileage in connection with the transfer of ownership` | Odometer ten-thousands digit |
| `undefined_2` | Odometer thousands digit |
| `Failure to` | Odometer hundreds digit |
| `undefined_3` | Odometer tens digit |
| `xx` | Odometer ones digit |
| `undefined_4` | Date read, month |
| `undefined_5` | Date read, day |
| `AND WE HEREBY CERTIFY THAT TO THE BEST OF OUR KNOWLEDGE` | Date read, year |

These were resolved by reading each widget's rectangle out of the PDF and sorting by
position on the page, not by guessing from the name. The digit order is confirmed by the
gap between the third and fourth box, which is where the thousands comma is printed
(23pt versus 18pt elsewhere).

**Nothing here should be mapped from its name alone.** The tables below give the position
meaning; the mapping must use the exact string from `docs/forms/82050-fields.txt`.

---

## Header — which form this is

| Field | Type | Class | Source / note |
|---|---|---|---|
| Notice of Sale Seller must complete sections 1  3 The purchasers signature in section 3 is optional | Button | D ⚠ | Notice of Sale mode. Which mode we generate depends on why we generate it at all — see ambiguity 1 |
| Bill of Sale Seller and purchaser must complete sections 1 2 when applicable  3 | Button | D ⚠ | Bill of Sale mode. Mutually exclusive with the above |

Note the double spaces inside both names; they are in the PDF and must be reproduced exactly.

## Section 1 — Vehicle description

Every field here is one the 82040 already fills, from the same key.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Year | Text | B | `year` |
| MakeManufacturer | Text | B | `make` |
| Body Type | Text | B | `body_type` |
| Model | Text | B | `model` |
| Color | Text | B | `color` |
| Iwe do hereby sell or have sold and delivered the above described motor vehicle mobile home | Text | B | **Certificate of Title Number.** `title_number`, unconditionally — unlike the 82040's box, this one is not state-qualified, so the Florida-only rule does not apply |
| Current Title Issue Date | Text | D | Not stored. The salvage title extractor reads the title number, state and brand, but not its issue date |
| VehicleVessel Identification Number | Text | B | `vin` |

## Section 1 — Purchaser and sale terms

| Field | Type | Class | Source / note |
|---|---|---|---|
| Print Names of Purchasers | Text | B ⚠ | `owner_name`, **if our customer is the purchaser** — see ambiguity 1 |
| Address City State Zip Code | Text | B ⚠ | One box for what we store in four: `['owner_address', 'owner_city', 'owner_state', 'owner_zip']`, which the config's array form already joins with spaces. Same caveat as above |
| 2 | Text | D | **Date of Sale.** Not stored anywhere: no `purchase_date` column on `vehicles` or `user_cases` |
| Odometer Disclosure Statement Required For a Motor Vehicle | Text | D | **Selling price.** Not stored |

## Section 2 — Odometer Disclosure Statement

Identical in meaning to Section 6 of the 82040 and Part A of the 82042, and every value is
already derived.

| Field | Type | Class | Source / note |
|---|---|---|---|
| 5 DIGIT OR | Button | D | Never ticked automatically, on any of the three forms: only a human can swear to what an old odometer physically displays |
| 6 DIGIT ODOMETER NOW READS | Button | B | `is_six_digit_odometer` |
| Text1 | Text | B | `odometer_digit_100000` |
| Federal and State law requires that you state the mileage in connection with the transfer of ownership | Text | B | `odometer_digit_10000` |
| undefined_2 | Text | B | `odometer_digit_1000` |
| Failure to | Text | B | `odometer_digit_100` |
| undefined_3 | Text | B | `odometer_digit_10` |
| xx | Text | B | `odometer_digit_1` |
| undefined_4 | Text | B | `odometer_date_mm` |
| undefined_5 | Text | B | `odometer_date_dd` |
| AND WE HEREBY CERTIFY THAT TO THE BEST OF OUR KNOWLEDGE | Text | B | `odometer_date_yyyy` |
| 1 REFLECTS THE ACTUAL MILEAGE | Button | B | `is_odometer_actual` |
| 2 IS IN EXCESS OF ITS MECHANICAL LIMITS | Button | B | `is_odometer_exceeds_limits` |
| 3 IS NOT THE ACTUAL MILEAGE | Button | B | `is_odometer_not_actual` |

> **The 82040 is the odd one out, confirmed.**
>
> | | 82040 | 82042 | 82050 |
> |---|---|---|---|
> | 1 | actual mileage | actual mileage | actual mileage |
> | 2 | **not the actual mileage** | in excess of limits | in excess of limits |
> | 3 | **in excess of limits** | not the actual mileage | not the actual mileage |
>
> Two of the three forms agree with each other and disagree with the 82040. The rule
> already applied to the 82042 holds here: map each key to the **sentence** in the field
> name, never to the leading digit.
>
> The wording differs too — `1  REFLECTS ACTUAL MILEAGE` (two spaces, no "THE"),
> `1. reflects ACTUAL MILEAGE` (dot, lower case) and `1 REFLECTS THE ACTUAL MILEAGE`
> (with "THE") are the same sentence on three forms. Field names can never be copied
> between forms, only re-dumped.

## Section 2 — Affidavit

| Field | Type | Class | Source / note |
|---|---|---|---|
| Affidavit When applicableRow1 | Text | D | Free text, used only when an odometer discrepancy needs explaining |
| Affidavit When applicableRow2 | Text | D | Second line of the same |

## Section 3 — Certification

All four signatures are wet and have no form fields; only the printed names and dates do.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Sellers Printed Name | Text | D ⚠ | The party selling. Unknown to us if that is the salvage yard; `owner_name` if it is our customer — see ambiguity 1 |
| Date | Text | D ⚠ | Seller's signing date, wet |
| Sellers Address City State Zip Code | Text | D ⚠ | Seller's address, on the same question |
| CoSellers Printed Name when applicable | Text | D | Co-seller. This business does not handle multi-party sales |
| Date_2 | Text | D | Co-seller's date |
| CoSellers Address when applicable City State Zip Code | Text | D | Co-seller's address |
| Purchasers Printed Name | Text | B ⚠ | `owner_name`, on the same role question as Section 1 |
| Date_3 | Text | D | Purchaser's signing date. Wet-signed on the day, like every other signature date in the packet |
| CoPurchasers Printed name when applicable | Text | D | Co-purchaser; not handled |
| Date_4 | Text | D | Co-purchaser's date |

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 20 | 50% |
| **B** — mappable now, no new data | 0 (+3 ⚠) | 7.5% |
| **C** — in the DB, needs surfacing | 0 | 0% |
| **D** — data we do not have | 12 (+5 ⚠) | 42.5% |

**Class C is empty for the third form running.** Every value this form needs that we hold
is already surfaced by `prepareFormData()`, so mapping it adds no keys, no columns and no
migration — the same finding as the 82042.

The 20 clean class B fields are the vehicle description and the entire odometer block.
Those are safe to map on the same terms as the 82042's Part A.

The three ⚠ class B fields are the ones that would carry our customer's name and address,
and they are only correct if our customer is the **purchaser**. That single question also
governs five of the ⚠ class D fields, so **ambiguity 1 below decides eight fields at
once** and should be settled before any of them is mapped.

## Reuse map

Every value the 82050 needs, and the `prepareFormData()` key that already supplies it. No
new keys, no new columns, no migration.

| 82050 field | Existing key | Already used by |
|---|---|---|
| Year | `year` | 82040 `Year`, 82042 `Year` |
| MakeManufacturer | `make` | 82040 `Make/Manufacturer`, 82042 `Make` |
| Body Type | `body_type` | 82040 `Body`, 82042 `Body Type` |
| Model | `model` | 82040 `Model` |
| Color | `color` | 82040 `Color`, 82042 `Color` |
| *(title number)* | `title_number` | **neither** — the 82040 maps the conditional `florida_title_number` instead |
| VehicleVessel Identification Number | `vin` | 82040 `…VIN` and `…VIN_2`, 82042 `Vehicle Identification Number` |
| 6 DIGIT ODOMETER NOW READS | `is_six_digit_odometer` | 82040, 82042 |
| Six digit boxes | `odometer_digit_100000` … `odometer_digit_1` | 82040 `100, 000 Place` …, 82042 `Hundred thousands place …` |
| Three date boxes | `odometer_date_mm` / `_dd` / `_yyyy` | 82040 `Date Read MM` …, 82042 `Two-digit month …` |
| 1 REFLECTS THE ACTUAL MILEAGE | `is_odometer_actual` | 82040 `1  REFLECTS ACTUAL MILEAGE`, 82042 `1. reflects ACTUAL MILEAGE` |
| 2 IS IN EXCESS OF ITS MECHANICAL LIMITS | `is_odometer_exceeds_limits` | 82040 **`3 IS IN EXCESS…`**, 82042 `2. is IN EXCESS…` |
| 3 IS NOT THE ACTUAL MILEAGE | `is_odometer_not_actual` | 82040 **`2 IS NOT THE ACTUAL…`**, 82042 `3. is NOT THE ACTUAL…` |
| Print Names of Purchasers ⚠ | `owner_name` | 82040 `Full Name of Applicant Owner`, 82042 `Owner/Purchaser Name` |
| Purchasers Printed Name ⚠ | `owner_name` | as above |
| Address City State Zip Code ⚠ | `['owner_address', 'owner_city', 'owner_state', 'owner_zip']` | 82040 maps these to four separate boxes |

Two rows deserve attention. `title_number` would be this form's **first** use of the
unconditional key: the 82040 deliberately uses `florida_title_number`, which blanks itself
for an out-of-state title, because its box names Florida. This box does not, so the raw
key is the correct one and the Florida rule must not be copied across.

The combined address box is also new. The config's array form (`['a', 'b']` joined with a
space) is handled by `mapFields()`, which skips empty parts, and has exactly one existing
use: the 82040's `Owner’s Name as It Appears on Driver License (First, Full Middle/Maiden,
& Last Name)` takes `['owner_first_name', 'owner_last_name']`. So the mechanism needs no
new code, but this would be its first use for an address, and a four-part join produces
`1841 NW 21st Terrace Miami FL 33142` with no commas. Worth eyeballing on a printed copy,
and worth considering whether the box wants punctuation the join does not add.

## Recommended order

1. **The 20 clean class B fields**, once a `generateHSMV82050()` field map exists. Same
   shape as the 82042 work: reuse the keys, map declarations by sentence, add a readback
   test with the `ReadsPdfFormFields` trait.
2. **The eight role-dependent fields**, as soon as ambiguity 1 is answered. ⚠
3. **Date of sale and selling price**, if bill-of-sale extraction lands. Both are on the
   document the customer already uploads, so an extractor could supply them the way the
   salvage title extractor supplies the title fields.
4. **Current title issue date**, likewise — it is on the salvage title we already read,
   and would need one more property on `SalvageTitleExtractor`.

Everything else is co-party blocks and wet signatures, which stay blank permanently.

## Ambiguous — confirm before mapping

1. **⚠ Who is selling, and why are we generating this form at all?** This decides eight
   fields and is the only question that matters here.

   The 82050 is completed **by the seller**. For a rebuilt title case the customer is the
   *purchaser*: they bought a salvage vehicle, and `config/documents.php` already describes
   the document we collect as "Bill of sale from when you purchased the vehicle". On that
   reading our customer's name goes in `Print Names of Purchasers` and
   `Purchasers Printed Name`, and the whole seller block belongs to a salvage yard or
   insurer we never store.

   But if the customer already holds that bill of sale and uploads it, generating a second
   one that only the original seller can sign is of little use. The alternatives are that
   the packet's 82050 is for the customer to **sell the rebuilt vehicle afterwards** — in
   which case every name and address flips and our customer is the seller — or that it
   exists to reconstruct an informal handwritten sale onto the official form.

   The two readings are mirror images. Guessing puts the customer's name on the wrong side
   of a document sworn under penalty of perjury.

2. **⚠ Notice of Sale or Bill of Sale?** The two header checkboxes select different
   obligations: a Notice of Sale needs only sections 1 and 3 and the purchaser's signature
   is optional, while a Bill of Sale needs section 2 as well and both parties sign. Follows
   directly from question 1, but should be stated explicitly.

3. **⚠ Should the packet contain an 82050 at all?** Related to question 1 and worth asking
   in the same breath. `generateFullPackage()` already calls `generateHSMV82050()`, so the
   answer is currently "yes" by default rather than by decision.

## Also found

`resources/views/inspector/inspection/documents.blade.php` mislabels two forms in the
inspector's document list:

| Form | Label in the view | Actual title |
|---|---|---|
| HSMV_82050 | "Statement of Builder — If vehicle was rebuilt by owner" | Notice of Sale and/or Bill of Sale |
| HSMV_82053 | "Odometer Disclosure Statement" | Power of Attorney for a Motor Vehicle |

"Statement of Builder" is HSMV **84490**, which the same list already names correctly, and
there is no separate odometer disclosure form in the packet — that is section 2 of this
one. Inspectors reading that screen are being told the wrong thing about which document
they are looking at. Not fixed here, since this is an audit, but it is a one-line
correction and it is further evidence that this form's role in the packet was never
pinned down.
