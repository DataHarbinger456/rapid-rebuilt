# HSMV 84490 — Field Coverage Audit

Every one of the 134 fillable fields in `docs/forms/84490.pdf`, classified against what the
system can supply today. Field names come from
`pdftk docs/forms/84490.pdf dump_data_fields_utf8`, dumped verbatim to
`docs/forms/84490-fields.txt`; copy the canonical name from there when writing a mapping.

The form is **Statement of Builder** (rev. 05-2024), four pages: Sections I–II on pages
1–2, Section III on page 2, Section IV on page 3, and Section V (HSMV office use, plus the
inspector attestations) on pages 3–4.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

There is no `hsmv_84490` entry in `config/hsmv_forms.php`, so **class A is empty**.
`generateHSMV84490()` exists — the only generator that takes the inspection as well as the
case — and reaches `fillForm()`, where it throws the "still in development"
`RuntimeException`. The form is in `PACKAGE_FORMS`, so every packet today carries it in
`MANIFEST.txt` under COMPLETE THESE BY HAND.

---

## Read this first: this form is mostly sworn statements and office use

The 84490 is not a data form with a signature at the bottom. It is a builder's
**declaration**, sworn twice, and its back half belongs to the state:

- **Section IV** carries a perjury clause covering the *whole document*: "Under penalties
  of perjury, I declare that I have read the foregoing document and that the facts provided
  herein are true. No material information … has been omitted." Everything printed above
  the applicant's signature — the parts table included — becomes their sworn statement.
- **Section III** (custom vehicle / street rod) is two explicit attestations under
  s.320.0863: one that the vehicle "will not be used for general daily transportation",
  one that it meets the equipment requirements of its model year. **Do not map
  `is_custom`/`is_street_rod` to these boxes.** Those keys describe a vehicle *type*;
  these checkboxes swear to *usage intent and equipment*, which only the owner knows.
  Ticking one from a vehicle_type would be a false attestation.
- **Section I items 4 and 5** are `(Initials)` boxes — a personal attestation that the
  vehicle is road operable / habitable. Initials cannot be typed truthfully by us.
- **Section V** ends with its own perjury statement: whoever completes it swears to the
  Yes/No matrix "based on that inspection". Pre-filling a matrix answer our inspector did
  not personally verify would put words in a sworn statement.

What we *can* fill truthfully is descriptive data already on file: the vehicle, the title,
the parts list the customer gave us, and the inspector's own recorded facts. That is still
the majority of the fillable area — but it must never bleed into the attestation boxes.

Second structural fact: the form has a dedicated **PRVIP Inspector** signature line, and
`Setting::get('prvip_license_number')` already exists in this codebase (currently empty).
PRVIP — the state's private inspector program — is very likely Rapid Rebuilt's own role
here, which makes Section V's PRVIP row ours to fill and connects directly to the
certifier question blocking the 82040's Section 8 and the 82042's Part B.

---

## Header — type of build

| Field | Type | Class | Source / note |
|---|---|---|---|
| REBUILT | Button | B | `is_rebuilt`, exports `On` |
| ASPT | Button | B | `is_assembled_from_parts`, exports `On` |
| KIT CAR | Button | B | `is_kit_car`, exports `On` |
| OTHER | Button | C ⚠ | `vehicle_type === 'other'` is stored but no key is derived for it (the 82040 has no OTHER box, so one was never needed) |
| Other Description | Text | D | Free text beside OTHER. Nothing stores a description of the build type |

## Section I — Description

| Field | Type | Class | Source / note |
|---|---|---|---|
| Motor Vehicle | Button | D ⚠ | Which of the three this is. No vehicle-class column exists anywhere, and it decides which sections of the form apply (motorcycles have a shorter component list; mobile homes add length and habitability) |
| Motorcycle | Button | D ⚠ | As above |
| Mobile Home | Button | D ⚠ | As above |
| Year | Text | B | `year` |
| Make | Text | B | `make` |
| Identification Number | Text | B | `vin` |
| Color | Text | B | `color` |
| Body | Text | B | `body_type` |
| Length | Text | D | Mobile home length. Not stored |
| Title Number | Text | B | `title_number` — this box is not state-qualified |
| Title State | Text | B | `title_state` |
| Other/Title Number | Text | D ⚠ | A second title pair. Nothing stores a second title number; whether this is "the salvage title" while item 2 is the new one, or a prior out-of-state title, is the question |
| Other/Title State | Text | D ⚠ | `previous_state_of_issue` is a candidate but only if the box means the prior titling state — confirm before mapping |
| Motor Vehicle/Motorcycly is complete and in road operable condition Initials | Text | D | Initials are a personal attestation. Note **Motorcycly**, a typo in the PDF's own field name — it is verbatim and must be copied as-is |
| Mobile Home is habitable for residential or commercial purposes Initials | Text | D | Initials, as above |

## Section II — Major component parts

### II.1 — the "purchased complete" branch

| Field | Type | Class | Source / note |
|---|---|---|---|
| Motor Vehicle_2 | Button | D ⚠ | Repeats the vehicle-class question we cannot answer |
| Motorcycle_2 | Button | D ⚠ | |
| Mobile Home was purchased | Button | D ⚠ | Name is truncated mid-sentence in the PDF; verbatim |
| This section is not applicable as the | Text | D | The seller this branch needs. Junk name inherited from the sentence above it — the box is the "purchased from ___" blank. No seller stored |
| Date Text | Text | D | Purchase date. Not stored on `vehicles` or `user_cases` |
| Year#2 | Text | D | The "20___" of the purchase date |

### II.2 — the parts table

Five rows, each `Part | New | Used | Repaired | Aftermarket | Homemade | Source/VIN`.
Columns were identified by left edge against the printed header (134/194/257/325/401).
Every name below is verbatim from the dump — the spacing quirks are in the PDF.

| Row | Part | New | Used | Repaired | Aftermarket | Homemade | Source/VIN |
|---|---|---|---|---|---|---|---|
| 1 | `Part` | `Check Box 4` | `Check Box 5` | `Check Box 6` | `Check Box 7` | `Check Box 8` | `Source/VIN` |
| 2 | `Part #1` | `Check Box9` | `Check Box10` | `Check Box11` | `Check Box 12` | `Check Box 13` | `Source/VIN #1` |
| 3 | `Part #2` | `Check Box 14` | `Check Box 15` | `Check Box 16` | `Check Box 17` | `Check Box 18` | `Source/VIN 2` |
| 4 | `Part #3` | `Check Box 24` | `Check Box 25` | `Check Box 26` | `Check Box 27` | `Check Box 28` | `Source/VIN #3` |
| 5 | `Part #4` | `Check Box28` | `Check Box29` | `Check Box 30` | `Check Box 31` | `Check Box 32` | `Source/VIN #4` |

Row 2's first three buttons have **no space after "Box"**; row 5's first two are the
same. `Check Box 28` (row 4, Homemade) and `Check Box28` (row 5, New) are two different
fields whose names differ by that space alone, numbers 19–23 do not exist, and row 3's
source is `Source/VIN 2` with **no `#`** where its four siblings have one.

| Fields | Type | Class | Source / note |
|---|---|---|---|
| The five `Part…` name boxes above | Text ×5 | C | `components.part_type`, humanized — e.g. `fender_front_left` → "Front Left Fender". Real cases hold 1–5 components, so the five rows fit today, with 84491 as the overflow the form itself provides |
| The 25 condition buttons above | Button ×25 | C ⚠ | `components.condition`. **The enum and the columns do not line up:** we store `new/used/repaired/rebuilt/original`, the form offers `New/Used/Repaired/Aftermarket/Homemade`. Three values map cleanly; `rebuilt` and `original` have no column, and `aftermarket` and `homemade` have no enum value. Needs a client answer before mapping |
| The five `Source/VIN` boxes above (rows 1–5 of the decoder table) | Text ×5 | C | `components.source_name` (source or donor VIN) |
| **3. When Section II(1) is not applicable, describe repairs made in detail** | **Sig** | D | A signature-type widget sitting on the first ruled line of the description box — almost certainly a form-authoring mistake, but it means only one of the two lines is a text field. The leading `3. ` is part of the field name |
| describe repairs in detail | Text | D | The second ruled line. No repairs narrative is stored anywhere; the parts table is the closest thing |
| Number of Receipts | Text | C ⚠ | Derivable — but from what? `components` with a `receipt_file_id`, or the uploaded `component_receipts` documents? The two counts can differ |

### II.3 — Section III: custom vehicle or street rod

| Field | Type | Class | Source / note |
|---|---|---|---|
| `The vehicle will not be used for general daily transportation but will be maintained for occasional` | Button | D ⚠ | Sworn attestation of usage intent under s.320.0863. Only the owner can make it — **never map `is_custom` here**. Name truncated mid-sentence in the PDF; verbatim. Exports `On` |
| `The vehicle meets state equipment and safety requirements for motor vehicles that were in effect in this` | Button | D ⚠ | Sworn attestation, as above. Verbatim truncated name. Exports `On` |
| Signature | Sig | D | Wet |
| Date | Text | D | Signed on the day |

## Section IV — Applicant information and signature

Two identical columns. The printed form gives no label distinguishing them.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Date_2 | Text | D | The applicant's signing date |
| Printed Name of ApplicantBusiness | Text | B | `owner_name` — if the applicant is the customer; see ambiguity 1 |
| Printed Name of ApplicantBusiness_2 | Text | D ⚠ | Second column, unknown owner |
| Street Address | Text | B | `owner_address` |
| Street Address_2 | Text | D ⚠ | |
| City / State / Zip | Text ×3 | B | `owner_city` / `owner_state` / `owner_zip` |
| City_2 / State_2 / Zip_2 | Text ×3 | D ⚠ | |
| Telephone NumberS | Text | B | `owner_phone` |
| Telephone NumberS_2 | Text | D ⚠ | |
| Signature Of ApplicantBusiness | Sig | D | Wet — this signature is the perjury clause |
| Signature Of ApplicantBusiness_2 | Sig | D | Wet |

## Section V — HSMV office use (page 3 descriptive block)

| Field | Type | Class | Source / note |
|---|---|---|---|
| VIN | Text | B | `vin` — pre-filling descriptive boxes helps whoever completes the rest |
| Title #1 | Text | B | `title_number` |
| Title State #1 | Text | B | `title_state` |
| Odometer | Text | B | `odometer` — one plain box here, unlike the 82040's per-digit row |
| Year #1 | Text | B | `year` |
| Make #1 | Text | B | `make` |
| Body #1 | Text | B | `body_type` |
| Color #1 | Text | B | `color` |
| `D-1`, `D-2`, `D-3`, `D-4` | Text ×4 | D ⚠ | Four wide unlabelled boxes. Nothing in the app records what they hold; `inspections.vin_location_1/2` ("VIN under windshield", "VIN on door jamb") are plausible candidates for two of them, but that is a guess — confirm |
| Audit # | Text | C | `inspections.audit_number` — **our own generated number** (`InspectionService::generateAuditNumber()`), already on every inspection |
| Region # | Text | C | `inspections.region_number`, recorded from the inspector's region |

## Section V (page 4) — the Yes/No matrix

Eleven rows, each a Yes/No pair. Button names run `Check Box 33`–`54`; the Yes button of
a pair is always the lower number. Export values are per-button and inconsistent —
recorded here from the dump because they cannot be assumed:

| Row | Yes button | exports | No button | exports |
|---|---|---|---|---|
| Secondary VIN Verified | `Check Box 33` | `Yes` | `Check Box 34` | `Yes` |
| Federal Decal | `Check Box 35` | `Yes` | `Check Box 36` | `Yes` |
| Replacement VIN Plate/Decal | `Check Box 37` | `Yes` | `Check Box 38` | `Yes` |
| Vehicle Painted Prior to Inspection | `Check Box 39` | `Yes` | `Check Box 40` | `Yes` |
| Odometer Replacement Notice | `Check Box 41` | `Yes` | `Check Box 42` | `Yes` |
| FRVIS | `Check Box 43` | `Yes` | `Check Box 44` | `Yes` |
| Previous Rebuilt | `Check Box 45` | **`On`** | `Check Box 46` | `Yes` |
| NICB Check | `Check Box 47` | **`On`** | `Check Box 48` | `Yes` |
| Component Parts Marked | `Check Box 49` | **`On`** | `Check Box 50` | `Yes` |
| Flood Damaged | `Check Box 51` | **`On`** | `Check Box 52` | `Yes` |
| Theft | `Check Box 53` | **`On`** | `Check Box 54` | `Yes` |

| Fields | Type | Class | Source / note |
|---|---|---|---|
| All 22 buttons above | Button ×22 | D ⚠ | All sit under the page-4 perjury clause, so an answer is a sworn statement by whoever gives it. Which rows a **PRVIP** inspector may answer, versus which wait for the HSMV Compliance Examiner, is the open question. Candidate data exists for some rows — `decal_number`, `is_flood`, `vin_verified`, `components_verified` — but recording a value is not the same as being entitled to swear to it |

| Field | Type | Class | Source / note |
|---|---|---|---|
| This ASPTVehicle resembles a | Text | D | Applies to ASPT/custom only; nothing stores it |
| Tax Due On | Text | D | HSMV office calculation |
| With Tongue / Without Tongue | Button ×2 | D | Mobile home measurement |
| Comments 1, Comments 2, Comments 3 | Text ×3 | C ⚠ | `inspections.inspector_notes` is the natural source, but whether this box is the PRVIP inspector's or the CE's is part of the same matrix question |
| Signature of HSMV | Sig | D | HSMV Compliance Examiner. Wet |
| Print Name of HSMV | Text | D | HSMV staff |
| Region | Text | D | The CE's region |
| Date_3 | Text | D | The CE's date |
| Signature of PRVIP Inspector | Sig | D | Wet — but this line is very likely **ours**; see ambiguity 2 |
| Print Name of PRVIP Inspector | Text | **B** | `inspector_name`, already derived for the 82040's Section 8 |
| CoAgy | Text | C ⚠ | The PRVIP inspector's company/agency number. `Setting::get('prvip_license_number')` exists for exactly this and is **empty**. Note the name is `CoAgy` in the dump; `Co/Agy` is only the FieldNameAlt |
| Date_4 | Text | **B** | `inspector_signed_date`, already derived |

---

> **Trap: the junk names are worse than the 82050's.**
>
> Five rows of checkboxes named `Check Box 4` through `Check Box 32`, and the spacing is
> inconsistent *inside the set*: `Check Box9`, `Check Box10`, `Check Box11` and
> `Check Box28`, `Check Box29` have **no space after "Box"**, while every other number
> has one. `Check Box28` (row 5, New) and `Check Box 28` (row 4, Homemade) are two
> different fields whose names differ by that space alone, and numbers 19–23 simply do
> not exist. Add `Source/VIN 2` without its `#`, `Motorcycly` misspelled in Section I,
> and `CoAgy` — none of these can be retyped by hand. Copy every name from
> `docs/forms/84490-fields.txt`.

> **Trap: this form mixes `On` and `Yes` exports on the same page.**
>
> The header and type buttons export `On`. The 25 parts-table condition buttons export
> `Yes`. In the page-4 matrix the left block's buttons export `Yes` while the right
> block's alternate between `Yes` and `On` for no visible reason. `checkboxValue()`
> defaults to `On`, so a mapping that omits the `on` key will silently leave some of
> these unticked — the same failure mode as the 82042's certifier buttons. Dump each
> button's export value individually; assume nothing.

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 0 | 0% |
| **B** — mappable now, no new data | 26 | 19% |
| **C** — in the DB, needs surfacing | 43 (most ⚠) | 32% |
| **D** — data we do not have | 65 (most ⚠) | 49% |

**Class C is populated for the first time in this audit series.** The 82040, 82042 and
82050 all reused `prepareFormData()` keys outright; this form does not. Its single
biggest block — the parts table, 30 of 134 fields — is data we already hold in
`components` (real cases carry 1–5 rows, and `part_type`'s enum tracks the form's own
05-2024 component list down to the EV parts), waiting to be surfaced. `audit_number` and
`region_number` are simpler: our own inspection data, one line each.

The class D half is dominated by two things that are *not* missing data: 22 matrix
buttons and 6 signature widgets. The matrix is blocked on who may answer, not on what we
know; the signatures are wet by law.

## Reuse map

| 84490 field | Existing key | Also used by |
|---|---|---|
| Identification Number / VIN | `vin` | 82040 (×2), 82042, 82050 |
| Year / Year #1 | `year` | all three |
| Make / Make #1 | `make` | all three |
| Color / Color #1 | `color` | all three |
| Body / Body #1 | `body_type` | all three |
| Title Number / Title #1 | `title_number` | 82050 (unconditional form), 82040 via `florida_title_number` |
| Title State / Title State #1 | `title_state` | 82050 |
| Odometer | `odometer` | **new use** — the 82040/82042/82050 split it into per-digit boxes; this form has one plain box |
| REBUILT / ASPT / KIT CAR | `is_rebuilt` / `is_assembled_from_parts` / `is_kit_car` | 82040 Section 3 |
| Printed Name of ApplicantBusiness | `owner_name` | 82040, 82042, 82050 |
| Street Address / City / State / Zip | `owner_address` / `owner_city` / `owner_state` / `owner_zip` | 82040 |
| Telephone NumberS | `owner_phone` | 82040 |
| Print Name of PRVIP Inspector | `inspector_name` | 82040 Section 8 |
| Date_4 | `inspector_signed_date` | 82040 Section 8 |

Everything class C has no key yet: the parts table reads `components`, the audit and
region numbers read `inspections`, and `CoAgy` reads a setting that is empty.

## Recommended order

1. ~~**The 20 class B fields that are genuinely unblocked.**~~ **DONE.** Header build
   type (3), Section I description (7), the Section V descriptive block (8) — VIN, title
   pair, odometer, year/make/body/color — and the PRVIP printed name and date (2), which
   are the inspector's own recorded facts rather than an attestation. All reuse proven
   keys; same shape as the 82042's Part A.

   Mapped in `config/hsmv_forms.php` under `hsmv_84490`, pinned by
   `tests/Feature/HSMV84490FieldMapTest.php`. Two things came out of it worth knowing:

   - `Title Number` here is **not** the 82040's rule. That form's box is labelled
     "Florida Title Number" and takes `florida_title_number`, which is null for an
     out-of-state title; this box asks for the title number full stop, so it takes
     `title_number` whatever state issued it.
   - The form now generates, so it moved out of the packet manifest's COMPLETE THESE BY
     HAND list and into GENERATED AND ENCLOSED. Since only 20 of 134 boxes fill and the
     enclosed copy is flattened, `PDFGeneratorService::PARTIALLY_FILLED_FORMS` prints a
     PARTLY FILLED — NOT READY TO SUBMIT caveat under it. Remove that entry when every
     box is either mapped or struck off, not merely when the map grows.
2. **The Section IV applicant fields**, once ambiguity 1 (builder identity) is answered.
   Six class B fields — the left column's printed name, street, city/state/zip and
   phone — map to `owner_*` keys **only if the customer signs as builder**; line 152's
   table carries the caveat on the name row. If Rapid Rebuilt is ever the builder, they
   map to the shop instead, which is why they wait rather than shipping with item 1.
3. ~~**Audit # and Region #.**~~ **DONE.** Two class C fields, our own data, no
   ambiguity in the mapping itself. Two things did need deciding:

   - `Region #` (Section V, page 3) is ours; `Region` (page-4 CE block) is a state
     officer's and stays blank. The names differ by one character.
   - `region_number` carries `InspectionService`'s `'FL'` fallback whenever the
     inspector has no region on file. `'FL'` is a state, not a region number, so
     `prepareFormData()` drops it and the box prints blank — a question staff can
     answer by hand beats a wrong value flattened onto a sworn form. Remove the guard
     (`PDFGeneratorService::UNSET_REGION_FALLBACK`) once region is required of every
     inspector.
4. **The parts table**, once ambiguity 6 (condition columns) is answered. The biggest
   single win on the form: 30 fields.
5. **CoAgy**, once the PRVIP number exists in settings.
6. Everything else stays blank by design: the attestation boxes, the initials boxes, the
   wet signatures, and the CE-only block.

## Ambiguous — confirm before mapping

Five of these are with the client as round 2 of the questions form
(`docs/82040-client-questions-gdoc.html`, 16 Aug): ambiguity 2 below is question 9 and
leads, because its answer likely settles the 82040 Section 8 and 82042 Part B certifier
question open since 5 Aug; ambiguity 1 is question 10, 4 is 11, 5 is 12, and 6 is 13.
Ambiguities 3, 7 and 8 are not sent yet — they follow from the PRVIP and vehicle-class
answers, so asking them now would mean asking twice.

1. **Who is the builder on Section IV, and what is the second column for?** The
   certificate is "Statement of *Builder*" under a perjury clause. Is the applicant the
   customer (rebuilt by owner) or Rapid Rebuilt (rebuilt by shop)? And the form prints
   two identical name/address/phone columns — co-applicant, business, or something else?
   The left column maps to `owner_*` keys only if the customer is the signer.
2. **Is your inspector a PRVIP inspector, and what number goes in Co/Agy?** The form has
   a dedicated PRVIP signature line, and this codebase already reads
   `settings.prvip_license_number` — which is empty. **Answering this likely settles the
   certifier question that blocks the 82040's Section 8 and the 82042's Part B as well**
   — PRVIP is not one of the five authorities the 82042 lists, so if this is the role,
   the whole certifier mapping story changes.
3. **Which of the eleven Yes/No rows may a PRVIP inspector answer?** All of them sit
   under a perjury clause. Some have candidate data (`decal_number`, `is_flood`,
   `vin_verified`, `components_verified`); knowing which rows are ours decides whether
   the matrix is class D-forever or pre-fillable.
4. **What are D-1 through D-4?** Four wide unlabelled boxes in Section V. Are they the
   VIN locations our inspectors already record ("VIN under windshield", "VIN on door
   jamb"), or HSMV findings we never see?
5. **Section I has two title pairs.** `Title Number / Title State` and
   `Other/Title Number / Other/Title State`. Which one holds the salvage title we
   extracted, and what belongs in the other — a prior out-of-state title
   (`previous_state_of_issue`), or nothing?
6. **The condition columns do not match the enum.** The form offers
   New/Used/Repaired/Aftermarket/Homemade; `components.condition` stores
   new/used/repaired/rebuilt/original. Which column do `rebuilt` and `original` print
   under, and should `aftermarket` and `homemade` be added to the enum — real data may
   already need them?
7. **Is the II.1 "purchased complete" branch ever used?** It covers a vehicle bought
   already in rebuilt/ASPT condition, and needs a seller and purchase date we do not
   store. If Rapid Rebuilt never uses it, six fields retire permanently.
8. **Do you handle motorcycles or mobile homes?** The Section I class buttons, Length,
   the habitability initials, and the tongue measurement all belong to them. If the
   business is motor vehicles only, a dozen fields stop mattering.
