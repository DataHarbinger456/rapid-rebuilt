# HSMV 82042 — Field Coverage Audit

Every one of the 35 fillable fields in `docs/forms/82042.pdf`, classified against what the
system can supply today. Field names come from
`pdftk docs/forms/82042.pdf dump_data_fields_utf8`, dumped verbatim to
`docs/forms/82042-fields.txt`; copy the canonical name from there when writing a mapping.

The form is **Vehicle Identification Number and Odometer Verification** (rev. 01/15/21),
two pages, of which page 2 is instructions with no fields.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

> **Updated 11 Aug 2026; revised 16 Aug.** The 20 clean class B fields are mapped, plus
> the 5-digit odometer box since the client's FIELD 05 answer, so **A is 21** and B is
> down to the single ⚠ field. `generateHSMV82042()` exists, `HSMV82042FieldMapTest`
> covers the map, and `generatePackageForm()` dispatches the form into every packet,
> gated on out-of-state titles. Everything still marked ⚠ or D below is untouched and
> still waiting on the client.

There was no `hsmv_82042` entry in `config/hsmv_forms.php` when this audit was written, so
class A started empty, and there was no `generateHSMV82042()` at all — the form was
behind even the other unmapped ones, which at least had generator methods that threw.
Of the remaining forms, the 82050 is now mapped too; the 82053, 84490 and 84491 still
throw for want of a field map.

---

## Part A — Owner's Vehicle Identification Affidavit

Completed by the **owner**, who certifies they physically inspected their own vehicle on
the date given.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Date - Part A | Text | D ⚠ | The date the *owner* inspected their own vehicle. We never record this. Not the inspection date — that is Part B |
| Vehicle Identification Number | Text | B | `vin` |
| Year | Text | B | `year` |
| Make | Text | B | `make` |
| Color | Text | B | `color` |
| Body Type | Text | B | `body_type` |
| Previous State Vehicle Titled In | Text | B | `previous_state_of_issue` — the same derived key the 82040 uses, which prefers `vehicles.previous_state` and falls back to `title_state` |

## Part A — Odometer Declaration

Identical in meaning to Section 6 of the 82040, and every value is already derived.
**The declaration options are numbered differently on the two forms** — see the trap note
below the table.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Select if 5 digit odometer reading | Button | A | **Mapped 13 Aug**, on all three forms. Ticked only by `inspections.odometer_digit_count`, which an inspector records on site (client's FIELD 05 answer); the model-year fallback can assert six digits but never five, so nothing but a human can tick this box |
| Select if 6 digit odometer reading | Button | A | `is_six_digit_odometer`. Inspector-recorded count wins; the model-year fallback (≥ 1995) applies only when none was recorded |
| Hundred thousands place that the odometer number now reads | Text | B | `odometer_digit_100000` |
| Ten thousands place that the odometer number now reads | Text | B | `odometer_digit_10000` |
| Thousands place that the odometer number now reads | Text | B | `odometer_digit_1000` |
| Hundreds place that the odometer number now reads | Text | B | `odometer_digit_100` |
| Tens place that the odometer number now reads | Text | B | `odometer_digit_10` |
| Ones place that the odometer number now reads | Text | B | `odometer_digit_1` |
| Two-digit month of odometer reading | Text | B | `odometer_date_mm` |
| Two-digit day of odometer reading | Text | B | `odometer_date_dd` |
| Four-digit year of odometer reading | Text | B | `odometer_date_yyyy` |
| 1. reflects ACTUAL MILEAGE | Button | B | `is_odometer_actual` |
| 2. is IN EXCESS OF ITS MECHANICAL LIMITS | Button | B | `is_odometer_exceeds_limits` — **note the number** |
| 3. is NOT THE ACTUAL MILEAGE | Button | B | `is_odometer_not_actual` — **note the number** |

> **Trap: the two forms number the declarations differently.**
>
> | | 82040 | 82042 |
> |---|---|---|
> | 1 | reflects actual mileage | reflects actual mileage |
> | 2 | **is not the actual mileage** | **is in excess of its mechanical limits** |
> | 3 | **is in excess of its mechanical limits** | **is not the actual mileage** |
>
> Options 2 and 3 are swapped. Mapping by position or by number rather than by meaning
> would silently swear that a vehicle with rolled-over mileage has had its odometer
> tampered with, or the reverse. Always map from the `is_odometer_*` key whose name
> matches the sentence, never from the leading digit in the field name.

## Part A — Signatures

| Field | Type | Class | Source / note |
|---|---|---|---|
| Owner/Purchaser Name | Text | B | `owner_name` — the printed name under the owner's signature |
| Seller's Name | Text | D | Printed name under the seller's signature. We never store the seller. The bill of sale we already collect names them, so AI extraction (S2 item 1) could supply it later |

Both signatures themselves are wet-signed and have no form field.

## Part B — Verification of the Vehicle Identification Number

This is the same certification as **Section 8 of the 82040**, on its own sheet. The form
allows a Notary, a Florida Licensed Dealer, a Law Enforcement or Military Police Officer,
or a Florida Compliance Examiner/Inspector (DMS or Tax Collector employee).

Everything in this part is gated on the **same open question** as the 82040's Section 8:
which of those five the client's inspectors actually are. That is ambiguity 3 in
`docs/82040-coverage.md`, already sent to the client, and one answer unblocks both forms.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Date - Part B | Text | B ⚠ | `inspector_signed_date` fits, but only if our own inspector is authorised to certify. If a third party signs Part B, it is their date, not ours |
| Name of Signer | Text | D ⚠ | Printed name of a non-notary certifier. `inspector_name` if our inspector signs; otherwise unknown |
| Commissioned Name of Florida Notary | Text | D ⚠ | Only when a notary certifies |
| Florida Compliance Examiner or Inspector DMS or Tax Collector employee | Button | D ⚠ | Certifier type. Export value **`Yes`**, not `On` |
| Military officer or Law Enforcement Officer | Button | D ⚠ | Certifier type. Export value **`Yes`** |
| Florida Licensed Dealer | Button | D ⚠ | Certifier type. Export value **`Yes`** |
| Florida Compliance Examiner/Inspector Name | Text | D ⚠ | Only when a compliance examiner certifies |
| Badge or ID Number | Text | D ⚠ | The examiner's badge or ID |
| Law Enforcement Agency Name | Text | D ⚠ | Only when an officer certifies |
| LEO Badge Number | Text | D ⚠ | The officer's badge |
| Florida Dealer Name | Text | D ⚠ | Only when a dealer certifies. Possibly `company_name`, if Rapid Rebuilt certifies under its own dealer licence |
| Florida Dealer Number | Text | D ⚠ | The dealer licence number. `settings.prvip_license_number` exists and may or may not be the right number |

The notary seal and all Part B signatures are wet and have no form fields.

> **Trap: three buttons export `Yes`, not `On`.**
> Every button on the 82040 exports `On`, and `PDFGeneratorService::checkboxValue()`
> defaults to `'On'` when a mapping omits the `on` key. The three certifier-type buttons
> here must set `'on' => 'Yes'` explicitly or they will silently stay unticked.

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 21 | 60% |
| **B** — mappable now, no new data | 0 (+1 ⚠) | 3% |
| **C** — in the DB, needs surfacing | 0 | 0% |
| **D** — data we do not have | 13 (all ⚠ but one) | 37% |

**The headline is how little new work this form needs.** Every class B field reuses a
`prepareFormData()` key that already exists for the 82040 — there is nothing to add to
`prepareFormData()` at all, and class C is empty for exactly that reason. The 82042 is
almost entirely a second view onto data the 82040 work already derived.

The 40% in class D is not fourteen separate problems. Twelve of those fields are the Part B
certifier block, and **one client answer fills or discards all twelve at once**: whichever
certifier type applies, its two or three fields get mapped and the other nine stay
permanently blank, because only one certifier signs any given form.

## Reuse map

Every value the 82042 needs, and the `prepareFormData()` key that already supplies it. No
new keys, no new columns, no migration.

| 82042 field | Existing key | Also used by the 82040 as |
|---|---|---|
| Vehicle Identification Number | `vin` | `Vehicle Identification Number VIN`, `…VIN_2` |
| Year | `year` | `Year` |
| Make | `make` | `Make/Manufacturer` |
| Color | `color` | `Color` |
| Body Type | `body_type` | `Body` |
| Previous State Vehicle Titled In | `previous_state_of_issue` | `Previous State of Issue` |
| Select if 6 digit odometer reading | `is_six_digit_odometer` | `6-digit odometer now reads` |
| Six odometer digit boxes | `odometer_digit_100000` … `odometer_digit_1` | `100, 000 Place` … `Ones Place` |
| Three date boxes | `odometer_date_mm` / `_dd` / `_yyyy` | `Date Read MM` / `DD` / `YYYY` |
| 1. reflects ACTUAL MILEAGE | `is_odometer_actual` | `1  REFLECTS ACTUAL MILEAGE` |
| 2. is IN EXCESS OF ITS MECHANICAL LIMITS | `is_odometer_exceeds_limits` | `3 IS IN EXCESS OF ITS MECHANICAL LIMITS` |
| 3. is NOT THE ACTUAL MILEAGE | `is_odometer_not_actual` | `2 IS NOT THE ACTUAL MILEAGE` |
| Owner/Purchaser Name | `owner_name` | `Full Name of Applicant Owner` |
| Date - Part B ⚠ | `inspector_signed_date` | `Date of Inspector Signature` |

Note the last three odometer rows: the same three keys, mapped to differently numbered
fields on each form. That table is the reason to map by meaning.

## Recommended order

1. ~~**The 20 clean class B fields.**~~ **Done 11 Aug**, plus the 5-digit box on 13 Aug:
   21 fields mapped, proven by `HSMV82042FieldMapTest`'s fill-and-read-back tests.
2. **Part B, once ambiguity 3 comes back.** One answer maps two or three fields and
   permanently retires the other nine. This is the only remaining work that touches
   this form's config.
3. **Seller's name**, if and when bill of sale extraction lands.

The old sequencing caveat is obsolete: three of the six forms are now mapped (the 82040,
this one and the 82050), `generateFullPackage()` collects each unmapped form's
`RuntimeException` into the manifest instead of aborting the packet, and this form is
dispatched in every packet and held back for Florida titles by `inapplicableReason()`.

Reuse `HSMV82040FieldMapTest`'s approach when mapping: fill the real template unflattened
through pdftk and read the values back, rather than asserting only the values handed to
pdftk. That is what catches a field name that does not exist. This form's names are
better behaved than the 82040's — **no curly apostrophes, and only one plain apostrophe,
in `Seller's Name`** — but the `Yes` export values are a fresh way to fail silently.

## Ambiguous — confirm before mapping

Two of these are new. The rest are the Part B certifier block, which is one question, and
it is already question 2 in `docs/82040-client-questions-gdoc.html`.

1. ~~**When is this form required at all?**~~ **Resolved 16 Aug: out-of-state titles only.**
   The instructions say it is for used vehicles *not currently titled in Florida*, and the
   client's 13 Aug answer says the same of the certification it carries: "out of state
   vessels only require this confirmation". `generateFullPackage()` now gates it on the
   same `title_state` test that drives the 82040's Transfer checkbox, so the two answers
   cannot drift apart, and a skipped form is named in the manifest under NOT REQUIRED FOR
   THIS CASE rather than dropped silently.

   Two narrower conditions in the instructions are **not** implemented: the 2,000 lb net
   weight floor, and the exclusions for new vehicles, mobile homes, light and travel
   trailers, and off-highway vehicles. `vehicles.weight` is free text off the title and
   often blank, and nothing records those body classes, so both would be guesses. A Florida
   dealer handling only rebuilt cars is unaffected; a business doing trailers and golf
   carts, which this one does, would eventually want them.
2. **Date - Part A.** ⚠ The date the owner physically inspected their own vehicle. We have
   no such date and it is not our inspection date. Likely left blank for the owner to
   write on the day, but confirm.
3. **The Part B certifier block** (12 fields). ⚠ Same question as the 82040's Section 8,
   already with the client. One answer resolves every one of them.
4. **The dealer number**, if the answer to 3 is "Florida licensed dealer." ⚠ Still open.
   Note the Section 7 work has since established the pattern: a dealer's licence number
   lives on their own user record (`users.license_number`, e.g. `1142356-1`), and the
   company-wide `settings.prvip_license_number` is empty and no longer used anywhere.
   Part B would read the same column for whichever dealer certifies — the open part is
   only *whether a dealer ever certifies*, which is ambiguity 3.
5. ~~**The 5-digit odometer box.**~~ **Resolved 13 Aug: mapped.** The client's FIELD 05
   answer is that inspectors record the digit count on site, so the box is driven by
   `inspections.odometer_digit_count` on all three forms and only a recorded 5 can tick
   it. See `OdometerDigitCountTest`.
