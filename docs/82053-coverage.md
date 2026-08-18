# HSMV 82053 — Field Coverage Audit

Every one of the 35 fillable fields in `docs/forms/82053.pdf`, classified against what the
system can supply today. Field names come from
`pdftk docs/forms/82053.pdf dump_data_fields_utf8`, dumped verbatim to
`docs/forms/82053-fields.txt`; copy the canonical name from there when writing a mapping.

The form is **Power of Attorney for a Motor Vehicle, Mobile Home, Vessel or Vessel with
Trailer** (rev. 06/16/22), one page. The `PACKAGE_FORMS` label 'Power of Attorney' was
verified against the PDF's printed title on 16 Aug and is correct.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

There is no `hsmv_82053` entry in `config/hsmv_forms.php`, so **class A is empty**.
`generateHSMV82053()` exists, reaches `fillForm()` and throws the "still in development"
`RuntimeException`. The form is in `PACKAGE_FORMS`, so every packet today carries it in
`MANIFEST.txt` under COMPLETE THESE BY HAND. It is also the form the
`test_unmapped_form_throws_instead_of_producing_a_blank_pdf` canary points at.

---

## Read this first: grantor, grantee, and which way the power flows

This is the only form in the packet whose entire purpose is a relationship between two
named parties rather than a description of a vehicle:

- **The grantor is the vehicle owner — the customer.** Every field in the two blocks at
  the bottom (name, signature, DL/FEID number, date of birth, address) belongs to them,
  and the two signature lines are wet by law: a power of attorney that is not signed by
  the grantor grants nothing.
- **The grantee — the attorney-in-fact — is whoever the grantor appoints.** Exactly one
  field on the form holds their name: `Full Legibly Printed Name is required`, a junk
  name inherited from the parenthetical printed under the blank. Everything the
  attorney-in-fact may do — apply for the title, register, transfer, sign the grantor's
  name — flows from that one appointment.

**Fields that would be a legal misstatement if filled from the wrong side:**

1. **The attorney-in-fact name filled with `owner_name`.** That is self-appointment: the
   owner granting power to themselves. The form collapses into nonsense, under its
   perjury clause.
2. **The attorney-in-fact name filled with Rapid Rebuilt's name without a real
   appointment.** The appointment is made individually by each customer when they sign;
   pre-printing the company as appointee on every copy implies appointments that were
   never given. Whether the business even uses this form that way is round 3's first
   question.
3. **`Driver License … or FEID Number of Owner` filled from `users.license_number`.**
   That column holds the *dealer* licence of a dealer account (VI-class, e.g.
   `1142356-1`), and null for private customers. It is not a driver licence number, and
   printing it here misstates the grantor's identity credential. The box wants the
   customer's DL/ID number, or a business's FEID — neither of which is stored anywhere.
4. **The date boxes filled with the generation date.** The appointment is dated the day
   the grantor signs, which nobody knows at generation time.
5. **Row 2 of the vehicle table filled by accident** — a second vehicle this power of
   attorney does not cover. One case is one vehicle; row 2 exists for multi-vehicle
   appointments and should stay blank unless the client says otherwise.
6. **A vehicle-class button that does not match the vehicle.** Ticking `Vessel` on a
   car's paperwork selects the wrong instrument entirely.

One connection worth carrying into the client questions: the form's own fine print says
this **non-secure** POA is the mechanism when the attorney-in-fact completes the odometer
disclosure "as the buyer only or the seller only" — and may never be used to sign as
both. That is the legal underpinning of the 82050's still-open "who signs the bill of
sale" question: if Rapid Rebuilt signs the 82050 for a customer, this form is *why* they
may, and this form is *what limits them* to one side. The two questions are one topic.

---

## Appointment line

| Field | Type | Class | Source / note |
|---|---|---|---|
| Month | Text | D | The date the grantor signs. Junk name; one of three date boxes |
| Day | Text | D | |
| Year | Text | D | |
| Full Legibly Printed Name is required | Text | D ⚠ | **The attorney-in-fact's name.** Junk name from the printed parenthetical. Who, if anyone, Rapid Rebuilt's customers appoint is round 3 question 1 |

## Vehicle class

Five buttons, "Please check only one of the following options". All export `On`.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Motor Vehicle | Button | D ⚠ | The vehicle-class question this series has deferred twice (84490 ambiguity 8, never sent). No column records what a case is |
| Mobile Home | Button | D ⚠ | |
| Vessel | Button | D ⚠ | |
| Vessel with an Untitled Trailer | Button | D ⚠ | Trailers under 2,000 lb |
| Vessel with a Titled Trailer | Button | D ⚠ | Trailers 2,000 lb or more |

## Vehicle table

Two rows, `Year | Make/Manufacturer | Body Type | Title Number | VIN/HIN`. Names carry a
`Row1`/`Row2` suffix fused to the label — `YearRow1`, not `Year Row1`.

| Field | Type | Class | Source / note |
|---|---|---|---|
| YearRow1 | Text | B | `year` |
| MakeManufacturerRow1 | Text | B | `make` |
| Body TypeRow1 | Text | B | `body_type` |
| Title NumberRow1 | Text | B | `title_number` — not state-qualified |
| Vehicle Identification Number VIN Hull Identification Number HINRow1 | Text | B | `vin` |
| The five `…Row2` fields below | Text ×5 | D ⚠ | A second vehicle on the same appointment. One case is one vehicle, so these stay blank unless the client files multi-vehicle POAs — round 3 question 2 |

| # | Field name, verbatim |
|---|---|
| 1 | `YearRow2` |
| 2 | `MakeManufacturerRow2` |
| 3 | `Body TypeRow2` |
| 4 | `Title NumberRow2` |
| 5 | `Vehicle Identification Number VIN Hull Identification Number HINRow2` |

## Grantor block — owner

The form's own heading: "Legibly Printed Name of Owner ("Grantor")".

| Field | Type | Class | Source / note |
|---|---|---|---|
| Legibly Printed Name of Owner Grantor | Text | B | `owner_name` — the grantor is the customer by definition, so no builder-identity ambiguity here |
| Signature of Owner Grantor | Sig | D | Wet. Without it the form grants nothing |
| Driver License Identification Card or FEID Number of Owner | Text | D | No DL, ID or FEID number is stored for a customer, and `users.license_number` is a dealer licence, not this — see misstatement 3 above |
| Date of Birth of Owner if applicable | Text | D | Not stored |
| Owners Address | Text | B | `owner_address`. **No apostrophe in the field name** — `Owners`, not `Owner's` |
| City | Text | B | `owner_city` |
| State | Text | B | `owner_state` |
| Zip Code | Text | B | `owner_zip` |

## Grantor block — co-owner

| Field | Type | Class | Source / note |
|---|---|---|---|
| Legibly Printed Name of CoOwner Grantor if applicable | Text | D | Co-owners are a scenario this business does not handle, as with the 82040's co-owner boxes |
| Signature of CoOwner Grantor | Sig | D | Wet |
| Driver License Identification Card or FEID Number of CoOwner | Text | D | |
| Date of Birth of CoOwner if applicable | Text | D | |
| CoOwners Address | Text | D | No apostrophe in the name |
| City_2 | Text | D | |
| State_2 | Text | D | |
| Zip Code_2 | Text | D | |

---

> **Trap: the two names that matter most are the two worst-named.**
>
> The attorney-in-fact's box is called `Full Legibly Printed Name is required`, and the
> date boxes are `Month`/`Day`/`Year` bare. Meanwhile every owner-block name drops the
> apostrophe the printed form shows (`Owners Address`, `CoOwners Address`) and every
> vehicle-table name fuses a `Row1`/`Row2` suffix onto the label. None of these survives
> retyping; copy from `docs/forms/82053-fields.txt`.

> **The whole form is under one perjury clause**, covering the appointment itself:
> filling any field from the wrong side of the grantor/grantee line is a sworn
> misstatement, not a typo. The grantor block is safe to pre-fill because it is factual
> owner data; everything that expresses the *appointment* — the grantee's name and the
> date — belongs to the signing moment.

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 0 | 0% |
| **B** — mappable now, no new data | 10 | 29% |
| **C** — in the DB, needs surfacing | 0 | 0% |
| **D** — data we do not have | 25 (11 ⚠) | 71% |

**First form in the series with no class C at all.** Everything here is either already
derived by `prepareFormData()` or not held anywhere — there is nothing in between. The B
half is small and safe (the grantor's factual block plus row 1 of the vehicle table); the
D half is dominated by the appointment itself, which only the client can direct, and the
co-owner block, which this business does not handle.

## Reuse map

| 82053 field | Existing key | Also used by |
|---|---|---|
| VIN/HIN Row1 | `vin` | 82040 (×2), 82042, 82050, 84490, 84491 |
| YearRow1 / MakeManufacturerRow1 / Body TypeRow1 | `year` / `make` / `body_type` | every other form |
| Title NumberRow1 | `title_number` | 82050, 84490 (unconditional) |
| Legibly Printed Name of Owner Grantor | `owner_name` | 82040, 82042, 82050, 84490, 84491 |
| Owners Address / City / State / Zip Code | `owner_address` / `owner_city` / `owner_state` / `owner_zip` | 82040, 84490 Section IV |

## Recommended order

1. **The ten class B fields** — the grantor's factual block and row 1 of the vehicle
   table — once there is a reason to generate this form at all. Nothing blocks them
   technically, but see 2.
2. **First, answer round 3 question 1.** If Rapid Rebuilt's customers never appoint an
   attorney-in-fact, this form does not belong in the packet at all and should join
   `inapplicableReason()`'s list, making item 1 moot. The 82042 set the pattern: gate
   first, map second.
3. The class buttons follow the vehicle-class answer (round 3 question 3), which also
   settles the 84490's and the 82050's vessel questions.
4. Everything else stays blank by design: the appointment line, the wet signatures, the
   co-owner block, and row 2.

## Ambiguous — confirm before mapping (round 3)

Checked against round 2, which is still pending: questions 9–13 (PRVIP, builder identity,
D-1 to D-4, the 84490's title pairs, condition columns) cover none of the following. The
82050's "who signs the bill of sale" ambiguity was also never sent — deferred when round
2 was assembled — and belongs here, because it is the same topic as question 1.

1. **Who is the attorney-in-fact, if anyone?** Do Rapid Rebuilt's customers appoint the
   company to file on their behalf — which this non-secure POA permits, limited to
   signing the odometer disclosure as buyer only or seller only — or is the form filed
   for some other appointee, or not used at all? **Ask together with the 82050's
   who-signs question**: if the company signs the 82050 for a customer, this form is the
   legal basis, and the answer to one largely answers the other.
2. **Does one power of attorney ever cover two vehicles** (the second table row), or is
   it always one per case?
3. **What does the business actually handle — motor vehicles only, or mobile homes,
   vessels, vessels with trailers too?** Deferred twice already (84490 ambiguity 8, and
   the "out of state vessels" reading of the 13 Aug FIELD 02 answer). This form's five
   class buttons, the 84490's Section I buttons and its mobile-home-only fields, and the
   82050's vessel wording all hang off the same answer.
4. *(Carried, not new)* — the 82050's **who is selling** question, folded into 1 above
   rather than asked twice.
