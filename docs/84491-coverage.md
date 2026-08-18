# HSMV 84491 — Field Coverage Audit

Every one of the 122 fillable fields in `docs/forms/84491.pdf`, classified against what the
system can supply today. Field names come from
`pdftk docs/forms/84491.pdf dump_data_fields_utf8`, dumped verbatim to
`docs/forms/84491-fields.txt`; copy the canonical name from there when writing a mapping.

The form is **Statement of Builder, Additional Sheet** (rev. 07/24), three pages: header and
a 12-row parts table on page 1, 19 ruled lines of repair description on page 2, and a
perjury signature block on page 3.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

There is no `hsmv_84491` entry in `config/hsmv_forms.php`, so **class A is empty**.
`generateHSMV84491()` exists, reaches `fillForm()` and throws the "still in development"
`RuntimeException`. The form is in `PACKAGE_FORMS`, so every packet today carries it in
`MANIFEST.txt` under COMPLETE THESE BY HAND.

---

## Read this first: this IS the 84490's additional sheet

The codebase contradicted itself about what this form is. The `generateHSMV84491()`
docblock said "Statement of Builder, Additional Sheet"; the `PACKAGE_FORMS` manifest label
said "Rebuilt Vehicle Inspection Application". **The PDF settles it**: the printed title is
"STATEMENT OF BUILDER … Additional Sheet", and every page's footer reads "STATEMENT OF
BUILDER FORM HSMV 84491". The docblock was right and the manifest label was wrong, and the
label is what staff read at the counter, so the label has been corrected in
`PDFGeneratorService.php`.

Being the 84490's continuation sheet has three consequences:

1. **It inherits the 84490's open questions rather than having its own.** The condition
   columns here are the same New/Used/Repaired/Aftermarket/Homemade set that does not line
   up with `components.condition` (84490 ambiguity 6). The page-3 signature block is the
   same double-column builder identity question (ambiguity 1). The vehicle-class buttons
   are the same motorcycle/mobile-home question (ambiguity 8). **Nothing here needs a new
   client question** — round 2 of the questions form already covers this form.
2. **The parts table is why this form exists.** The 84490 carries five rows; real cases
   hold up to five components today, so the main sheet is nearly always enough. This
   sheet's 12 rows plus 19 description lines are the overflow the 84490's own small print
   points at ("if additional space is needed, please use form HSMV 84491"). Whether it
   belongs in every packet or only on overflow is an operational decision for us, not the
   client — the same shape as the 82042's out-of-state gate.
3. **`generateHSMV84491()` already injects the data it will need** — it is the only
   generator that adds `$data['components'] = $case->components` on top of
   `prepareFormData()`. Whoever maps this form starts one step ahead of where the 84490's
   mapping started.

As on the 84490, page 3's block is sworn: "UNDER PENALTIES OF PERJURY … NO MATERIAL
INFORMATION … HAS BEEN OMITTED." Everything printed above the signature, including all 84
parts-table widgets, becomes the signer's sworn statement.

---

## Header — type of build

Identical to the 84490's header.

| Field | Type | Class | Source / note |
|---|---|---|---|
| REBUILT | Button | B | `is_rebuilt`, exports `On` |
| ASPT | Button | B | `is_assembled_from_parts`, exports `On` |
| KIT CAR | Button | B | `is_kit_car`, exports `On` |
| OTHER | Button | C ⚠ | `vehicle_type === 'other'` is stored but no key is derived — inherits 84490's finding |
| Other | Text | D | Free text beside OTHER. Junk name: this is the OTHER-description box. Nothing stores a build-type description |

## Section I — Description

| Field | Type | Class | Source / note |
|---|---|---|---|
| Motor Vehicle | Button | D ⚠ | The vehicle-class question, inherited from the 84490 (its ambiguity 8) |
| Motorcycle | Button | D ⚠ | |
| Mobile Home | Button | D ⚠ | |
| Year | Text | B | `year` |
| Make | Text | B | `make` |
| Identification Number | Text | B | `vin` |
| Color | Text | B | `color` |
| Body | Text | B | `body_type` |
| Length | Text | D | Mobile home length. Not stored |

## Section II — the 12-row parts table

Same seven columns as the 84490's table — `Part | New | Used | Repaired | Aftermarket |
Homemade | Source/VIN` — identified by left edge against the printed header
(137/195/257/326/402). Every name below is verbatim from the dump, and the names are the
worst in the series: the part boxes are literally named `1` through `8` and then `1_2`
through `4_2`, and the source boxes `1_3` through `4_3`, `5_2` through `8_2`, then `9`
through `12`.

| Row | Part | New | Used | Repaired | Aftermarket | Homemade | Source/VIN |
|---|---|---|---|---|---|---|---|
| 1 | `1` | `Check Box58` | `Check Box59` | `Check Box60` | `Check Box61` | `Check Box62` | `1_3` |
| 2 | `2` | `Check Box63` | `Check Box64` | `Check Box65` | `Check Box66` | `Check Box67` | `2_3` |
| 3 | `3` | `Check Box68` | `Check Box70` | `Check Box71` | `Check Box72` | `Check Box73` | `3_3` |
| 4 | `4` | `Check Box74` | `Check Box75` | `Check Box76` | `Check Box77` | `Check Box78` | `4_3` |
| 5 | `5` | `Check Box79` | `Check Box80` | `Check Box81` | `Check Box82` | `Check Box83` | `5_2` |
| 6 | `6` | `Check Box84` | `Check Box85` | `Check Box86` | `Check Box87` | `Check Box88` | `6_2` |
| 7 | `7` | `Check Box89` | `Check Box90` | `Check Box91` | `Check Box92` | `Check Box93` | `7_2` |
| 8 | `8` | `Check Box94` | `Check Box95` | `Check Box96` | `Check Box97` | `Check Box98` | `8_2` |
| 9 | `1_2` | `Check Box99` | `Check Box100` | `Check Box101` | `Check Box102` | `Check Box103` | `9` |
| 10 | `2_2` | `Check Box104` | `Check Box105` | `Check Box106` | `Check Box107` | `Check Box108` | `10` |
| 11 | `3_2` | `Check Box109` | `Check Box110` | `Check Box111` | `Check Box112` | `Check Box113` | `11` |
| 12 | `4_2` | `Check Box114` | `Check Box115` | `Check Box116` | `Check Box117` | `Check Box118` | `12` |

Row 3's buttons run 68 then **70–73: `Check Box69` does not exist**. Row 8's appear
out of order in the dump because `Check Box96` sits one point lower than its row-mates;
by left edge it is the Repaired column, as tabled. `Check Box 96` with a space is not a
field on this form — unlike the 84490, where `Check Box 28` and `Check Box28` are two
different fields, here every button is space-free.

| Fields | Type | Class | Source / note |
|---|---|---|---|
| The twelve `Part` boxes above (`1`…`4_2`) | Text ×12 | C | `components.part_type`, humanized, rows 6–17 of the case's list once the 84490's five are placed |
| The 60 condition buttons above | Button ×60 | C ⚠ | `components.condition`, same enum mismatch as the 84490 — gated on its ambiguity 6, not on anything local |
| The twelve `Source/VIN` boxes above (`1_3`…`12`) | Text ×12 | C | `components.source_name` |

## Page 2 — repair description

| Field | Type | Class | Source / note |
|---|---|---|---|
| The nineteen fields below | Text ×19 | D | Nineteen ruled lines, each named with the full phrase plus its row number. No repairs narrative is stored anywhere; nothing in the system can write these today. If a narrative is ever wanted it is a product decision (a repairs-notes field on the case), not a mapping |

| # | Field name, verbatim |
|---|---|
| 1 | `Please continue to describe any repairs made 1` |
| 2 | `Please continue to describe any repairs made 2` |
| 3 | `Please continue to describe any repairs made 3` |
| 4 | `Please continue to describe any repairs made 4` |
| 5 | `Please continue to describe any repairs made 5` |
| 6 | `Please continue to describe any repairs made 6` |
| 7 | `Please continue to describe any repairs made 7` |
| 8 | `Please continue to describe any repairs made 8` |
| 9 | `Please continue to describe any repairs made 9` |
| 10 | `Please continue to describe any repairs made 10` |
| 11 | `Please continue to describe any repairs made 11` |
| 12 | `Please continue to describe any repairs made 12` |
| 13 | `Please continue to describe any repairs made 13` |
| 14 | `Please continue to describe any repairs made 14` |
| 15 | `Please continue to describe any repairs made 15` |
| 16 | `Please continue to describe any repairs made 16` |
| 17 | `Please continue to describe any repairs made 17` |
| 18 | `Please continue to describe any repairs made 18` |
| 19 | `Please continue to describe any repairs made 19` |

## Section III (page 3) — signature block

The same double-column applicant block as the 84490's Section IV, under the same perjury
clause, but only name and signature here — no address or phone.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Date | Text | D | Signed on the day |
| Printed Name of ApplicantBusiness | Text | B | `owner_name` — **only if the customer signs as builder**; inherits 84490 ambiguity 1 |
| Printed Name of ApplicantBusiness_2 | Text | D ⚠ | Second column, unknown owner — the same open question as the 84490's `_2` column |
| Signature Of ApplicantBusiness | Sig | D | Wet — this signature is the perjury clause |
| Signature Of ApplicantBusiness_2 | Sig | D | Wet |

---

> **Trap: the junk names are pure numerals.**
>
> The part boxes are named `1` through `8` and then `1_2` through `4_2`; the source boxes
> `1_3` through `4_3`, `5_2` through `8_2`, then `9` through `12`. Two different rows can
> hold fields whose names differ only by an underscore suffix, and row 9's part box is
> named `1_2` while row 5's source box is named `5_2`. `Check Box69` does not exist. Copy
> every name from `docs/forms/84491-fields.txt`; none of this survives being retyped.

> **Trap: mixed `On` and `Yes` exports, same as the 84490.**
>
> The seven header and Section I buttons export `On`; all 60 parts-table buttons export
> `Yes`. `checkboxValue()` defaults to `On`, so a mapping that omits the `on` key leaves
> every condition box silently unticked.

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 0 | 0% |
| **B** — mappable now, no new data | 9 | 7% |
| **C** — in the DB, needs surfacing | 85 (most ⚠) | 70% |
| **D** — data we do not have | 28 | 23% |

**The shape is the opposite of the 84490's.** There, B/C/D ran 26/43/65; here it is
9/85/28, because this form is almost nothing but the parts table — 84 of its 122 fields —
plus 19 description lines nothing can fill. Its destiny is tied to the 84490's item 4
(the parts-table mapping): one answer on condition columns unlocks 144 fields across both
forms at once.

## Reuse map

| 84491 field | Existing key | Also used by |
|---|---|---|
| Identification Number | `vin` | 82040 (×2), 82042, 82050, 84490 |
| Year / Make / Color / Body | `year` / `make` / `color` / `body_type` | all four other forms |
| REBUILT / ASPT / KIT CAR | `is_rebuilt` / `is_assembled_from_parts` / `is_kit_car` | 82040 Section 3, 84490 header |
| Printed Name of ApplicantBusiness | `owner_name` | 82040, 82042, 82050, 84490 |

Everything class C has no key yet, and none of it should get one until the 84490's
parts-table work names the keys — the same data must not be surfaced twice under two
names.

## Recommended order

1. **Do not map this form first.** Its nine class B fields are a subset of the 84490's
   twenty, and its 85 class C fields are the same `components` data under worse field
   names. Mapping the 84490 (its items 1–4) does the work once for both.
2. **After the 84490's parts table lands**, map this sheet's table from the same keys,
   filling rows 6–17 of a case's component list. The generator already passes
   `$data['components']`.
3. **Decide when the sheet enters the packet** — every packet, or only when a case has
   more than five components or a repair narrative. Ours to decide from data, the same
   shape as the 82042's out-of-state gate; `inapplicableReason()` is the pattern.
4. Everything else stays blank by design: the 19 description lines, the wet signatures,
   and the second name column.

## Ambiguous — confirm before mapping

**This form has no new client questions.** Everything ⚠ above is inherited from the 84490's
audit and is already with the client as round 2 of the questions form:

- the `_2` name column and builder identity — 84490 ambiguity 1, question 10
- the condition columns versus `components.condition` — 84490 ambiguity 6, question 13
- the vehicle-class buttons — 84490 ambiguity 8, deliberately unsent pending the PRVIP
  and vehicle-class answers

The one open item that is ours, not the client's: **when does this sheet belong in the
packet** — always, or only on overflow past the 84490's five rows? Answerable from
`components` counts alone once the table is mapped.
