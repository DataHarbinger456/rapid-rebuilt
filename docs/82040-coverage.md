# HSMV 82040 — Field Coverage Audit

Every one of the 215 fillable fields in `docs/forms/82040.pdf`, classified against what
the system can supply today. Field names come from
`pdftk docs/forms/82040.pdf dump_data_fields_utf8`; eight very long ones are abbreviated
with `…` for readability here, so always copy the canonical name from
`docs/forms/82040-fields.txt` when writing a mapping.

**Classes**

| | Meaning |
|---|---|
| **A** | Already mapped in `config/hsmv_forms.php` |
| **B** | Mappable now — `prepareFormData()` already returns the value |
| **C** | Stored in the DB, but not yet surfaced by `prepareFormData()` |
| **D** | We do not have the data — needs AI extraction, a new form input, or a wet signature |

⚠ marks a field where the correct source is genuinely ambiguous and should be confirmed
with the client rather than guessed. Those are collected at the end.

---

## Header — Application Type / Print Request / Off-Highway Type

| Field | Type | Class | Source / note |
|---|---|---|---|
| Original | Button | A | `title_state = 'FL'` |
| Transfer | Button | A | `title_state` is any other state |
| No | Button | D ⚠ | "Request to print Certificate of Title" — a business policy choice, not data |
| Yes In office | Button | D ⚠ | same policy choice |
| Yes Mailed | Button | D ⚠ | same policy choice |
| AllTerrain Vehicle ATV | Button | D | Off-highway type; not stored. `vehicles.vehicle_type` is a *rebuilt* classification, not this |
| Recreational Off-Highway Vehicle (ROV | Button | D | as above |
| Off-Highway Motorcycle (OHM) | Button | D | as above |

## Section 1 — Owner / Applicant Information

| Field | Type | Class | Source / note |
|---|---|---|---|
| Customer Number | Text | D | FLHSMV-assigned; normally left blank |
| Fleet Number | Text | D | fleet applicants only |
| Unit Number | Text | D | fleet applicants only |
| Owner s County of Residence | Text | B | `owner_county` (`users.county`) |
| Tenancy by the Entirety | Button | D | joint-ownership election; needs a new input |
| Life EstateRemainder Person | Button | D | as above |
| With Rights of Survivorship | Button | D | as above |
| Joint Ownership: OR | Button | D | as above |
| Joint Ownership: AND | Button | D | as above |
| Owners Email Voluntary | Text | B | `owner_email` |
| Owners Mailing Address | Text | B ⚠ | `owner_address` — we store one address; mailing vs residential is undecided |
| Owner’s Name as It Appears on Driver License (…) | Text | A | joined first + last |
| Owner’s Phone Number (Voluntary) | Text | B | `owner_phone` |
| Owner's Sex | Text | D | on the licence; AI extraction from the DL scan |
| Owner's Date of Birth | Text | D | AI extraction from the DL scan |
| Owner's FL DL/ID or FEID/Suffix Number | Text | D | AI extraction from the DL scan |
| Owner's City | Text | B | `owner_city` |
| Owner's State | Text | B | `owner_state` (`users.states`) |
| Owner's Zip Code | Text | B | `owner_zip` |
| Owner's Residential Street Address | Text | A | `owner_address` |
| Owner’s Residential City | Text | B ⚠ | same single stored address as the mailing block |
| Owner’s Residential State | Text | B ⚠ | as above |
| Owner’s Residential Zip Code | Text | B ⚠ | as above |
| Are you a Florida Resident | Button | D | YES/NO attestation; new input |
| Are you a US Citizen | Button | D | YES_2/NO_2 attestation; new input |
| Are you deaf or hard of hearing Voluntary | Button | D | YES_3/NO_3; new input |
| Mail To Customer Name (If different…) | Text | D | only when mailing elsewhere; new input |
| Mail To’s Phone Number (Voluntary) | Text | D | as above |
| Mail To’s Email (Voluntary) | Text | D | as above |
| Mail To’s Sex | Text | D | as above |
| Mail To’s Date of Birth | Text | D | as above |
| Mail To’s FL DL/ID or FEID/Suffix Number | Text | D | as above |
| Mail To’s Address (If different…) | Text | D | as above |
| Mail To’s City | Text | D | as above |
| Mail To’s State | Text | D | as above |
| Mail To’s Zip Code | Text | D | as above |
| Co-Owner | Button | D | no co-owner concept in the schema |
| Lessee | Button | D | as above |
| Co-Owner or Lessee’s Name as It Appears on Driver License (…) | Text | D | as above |
| Co-Owner’s Phone Number (Voluntary) | Text | D | as above |
| Co-Owner’s Email (Voluntary) | Text | D | as above |
| Co-Owner’s Sex | Text | D | as above |
| Co-Owner’s Date of Birth | Text | D | as above |
| Co-Owner’s FL DL/ID or FEID/Suffix Number | Text | D | as above |
| Co-Owner’s/Lessee’s Mailing Address | Text | D | as above |
| Co-Owner’s/Lessee’s Mailing City | Text | D | as above |
| Co-Owner’s/Lessee’s Mailing State | Text | D | as above |
| Co-Owner’s/Lessee’s Mailing Zip Code | Text | D | as above |
| CoOwnersLessees Residential Street Address | Text | D | as above |
| Co-Owner’s/Lessee’s Residential City | Text | D | as above |
| Co-Owner’s/Lessee’s Residential State | Text | D | as above |
| Co-Owner’s/Lessee’s Residential Zip Code | Text | D | as above |
| Co-Owner Are you a Florida Resident | Button | D | as above |
| Co-Owner Are you a US Citizen | Button | D | as above |
| Co-Owner Are you deaf or hard of hearing? | Button | D | as above |

## Section 2 — Motor Vehicle Description

| Field | Type | Class | Source / note |
|---|---|---|---|
| Vehicle Identification Number VIN | Text | A | `vin` |
| Florida Title Number | Text | B | `title_number` (`vehicles.title_number`) |
| License Plate Number | Text | C | `vehicles.license_plate` |
| Previous State of Issue | Text | C | `vehicles.previous_state` |
| Model | Text | B | `model` |
| Year | Text | A | `year` |
| Body | Text | A | `body_type` |
| Color | Text | A | `color` |
| Make/Manufacturer | Text | A | `make` |
| Weight | Text | A | `vehicles.weight`. Resolved 5 Aug: every Florida title carries its own weight, so it is transcribed from the title (AI extraction), never from GVWR. Out-of-state titles may not have one — the box is deliberately left blank for staff to complete by hand |
| GVW | Text | D | a separate box from Weight. The VIN decoder's GVWR is not the title's GVW and we persist neither |
| BHPCC | Text | D | brake horsepower / cc; motorcycles only |
| Length in Feet | Text | D | vessels only |
| Length in Inches | Text | D | vessels only |
| Passenger | Button | D | van use; not stored |
| Other | Button | D ⚠ | unlabelled in the dump; most likely the second Van Use box, unconfirmed |
| Natural Gas Liquid | Button | C | `vehicles.fuel_type` |
| Natural Gas Compressed | Button | C | `vehicles.fuel_type` |
| Hybrid GasElectric | Button | C | `vehicles.fuel_type` |
| Hybrid DieselElectric | Button | C | `vehicles.fuel_type` |
| Electric | Button | C | `vehicles.fuel_type` |

## Section 3 — Brands, Usage and Type

The TYPE boxes come from `vehicles.vehicle_type`, an enum of `rebuilt, aspt, kit_car,
custom_vehicle, street_rod, replica, mobile_home, other` collected at intake. The BRAND
boxes come from `vehicles.title_brand`. The two are independent: a flood-branded ASPT
build ticks one of each. `mobile_home` and `other` have no box here and correctly tick
nothing.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Rebuilt | Button | A | `vehicle_type = 'rebuilt'` |
| Assembled from Parts | Button | A | `vehicle_type = 'aspt'` |
| Kit Car | Button | A | `vehicle_type = 'kit_car'` |
| Custom | Button | A | `vehicle_type = 'custom_vehicle'` |
| Street Rod | Button | A | `vehicle_type = 'street_rod'` |
| Replica | Button | A | `vehicle_type = 'replica'` |
| Flood | Button | A | `title_brand = 'flood'` |
| Electric_2 | Button | C | `vehicles.fuel_type` (duplicate of the Section 2 box) |
| Autonomous | Button | D | not stored |
| Bonded Title | Button | D | not stored |
| Glider Kit | Button | D | not stored |
| ILEV | Button | D | not stored |
| Long Term Lease | Button | D | not stored |
| Manuf Buy Back | Button | D | not stored |
| Police Veh | Button | D | not stored |
| Private Use | Button | D | not stored |
| Short Term Lease | Button | D | not stored |
| Taxicab | Button | D | not stored |

## Section 4 — Lienholder Information

No lienholder data exists anywhere in the schema. Every field here is class D.

| Field | Type | Class | Source / note |
|---|---|---|---|
| ELT Customer | Button | D | YES_7/NO_7 |
| FEID/Suffix # | Button | D | |
| DMV Account # | Button | D | |
| DL/ID #, Sex, and Date of Birth | Button | D | |
| Date of Lien | Text | D | |
| Lienholder's FEID/Suffix # DMV Account # DL/ID #, Sex and DOB | Text | D | |
| Lienholder’s Name (…) | Text | D | |
| Lienholders Mailing Address | Text | D | |
| Lienholder’s City | Text | D | |
| Lienholder’s State | Text | D | |
| Lienholder’s Zip Code | Text | D | |
| Lienholder’s Phone Number Voluntary | Text | D | |
| Lienholder’s Email Voluntary | Text | D | |
| Check this box if you, lienholder representative, authorize… | Button | D | |
| Signature to send Title to Owner | Signature | D | wet signature |

## Section 5 — Transfer Type

| Field | Type | Class | Source / note |
|---|---|---|---|
| Sale | Button | D | AI extraction from the bill of sale, or a new input |
| Gift | Button | D | as above |
| Repossession | Button | D | as above |
| Court Order | Button | D | as above |
| Inheritance | Button | D | as above |
| Other Transer Type | Button | D | as above |
| Other Specify Transfer Type | Text | D | as above |
| Sale Price in US Dollars | Text | D | AI extraction from the bill of sale |
| Sale Price in Cents | Text | D | AI extraction from the bill of sale |
| Date Acquired MM | Text | D | AI extraction from the bill of sale |
| Date Aquired DD | Text | D | AI extraction from the bill of sale |
| Date Aquired YYYY | Text | D | AI extraction from the bill of sale |
| Even trade or trade down | Button | D | dealer trade scenario |
| Terms of even trade or trade down | Text | D | dealer trade scenario |

## Section 6 — Odometer Declaration

| Field | Type | Class | Source / note |
|---|---|---|---|
| 1  REFLECTS ACTUAL MILEAGE | Button | B | `odometer_status = 'actual'` |
| 2 IS NOT THE ACTUAL MILEAGE | Button | B | `odometer_status = 'not_actual'` |
| 3 IS IN EXCESS OF ITS MECHANICAL LIMITS | Button | B | `odometer_status = 'exceeds_limits'` |
| 100, 000 Place | Text | B | `odometer` split per digit |
| 10,000 Place | Text | B | `odometer` split per digit |
| 1,000 Place | Text | B | `odometer` split per digit |
| 100 Place | Text | B | `odometer` split per digit |
| 10 Place | Text | B | `odometer` split per digit |
| Ones Place | Text | B | `odometer` split per digit |
| Date Read DD | Text | C | `vehicles.odometer_date` |
| Date Read MM | Text | C | `vehicles.odometer_date` |
| Date Read YYYY | Text | C | `vehicles.odometer_date` |
| 6-digit odometer now reads | Button | D ⚠ | whether the cluster is 5- or 6-digit is not recorded anywhere |
| 5-digit odometer now reads | Button | D ⚠ | as above |

## Section 7 — Dealer Sales Tax Report and Trade In

Applies to a selling dealer, not to Rapid Rebuilt as the filing agent, so most of this is
out of scope for a customer-filed application.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Florida Sales Tax Registration Number | Text | C ⚠ | `settings.tax_id` exists, but this box is the *selling dealer's* number |
| Dealer License Number | Text | D ⚠ | `settings.prvip_license_number` is an inspection licence, not a dealer licence |
| Date of Sale | Text | D | |
| Amount of Tax | Text | D | |
| DealerAgent Signature | Text | D | wet signature |
| Year of Trade In | Text | D | |
| Make of Trade In | Text | D | |
| Title Number of Trade In If known | Text | D | |
| Vehicle Identification Number VIN of Trade In | Text | D | |

## Section 8 — Motor Vehicle Identification Number Verification

This is the section that overlaps the inspection, and it is the most valuable unmapped
block on the form.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Vehicle Identification Number VIN_2 | Text | B | `vin` |
| Name Certifying Inspector | Text | C | `users` via `inspections.inspector_id` |
| Date of Inspector Signature | Text | C | `inspections.signed_at`, else `completed_at` |
| Signature of Certifying Inspector | Signature | C ⚠ | `inspections.inspector_signature` holds base64; stamping an image into a signature field is a different pdftk operation than filling text |
| Florida Notary Public Stamp or Seal | Button | D ⚠ | which of the five certifier types applies depends on how the client's inspectors are licensed |
| Law Enforcement | Button | D ⚠ | as above |
| Florida Dealer | Button | D ⚠ | as above |
| FLHSMV | Button | D ⚠ | as above |
| Tax Collector or | Button | D ⚠ | as above |
| Agency Name | Text | D | law-enforcement certifier only |
| Badge Number | Text | D | law-enforcement certifier only |
| Agency Name_2 | Text | D | law-enforcement certifier only |
| CountyAgency | Text | D | tax-collector certifier only |
| Dealer Name | Text | D ⚠ | only if the client certifies as a Florida dealer |
| Dealer Number | Text | D ⚠ | as above |
| Office Name | Text | D | FLHSMV/TC certifier only |
| User IDBadge | Text | D | FLHSMV/TC certifier only |

## Section 9 — Sales Tax Exemption Certification

| Field | Type | Class | Source / note |
|---|---|---|---|
| Purchaser state agencies counties etc holds valid exemption certificate | Button | D | |
| Vehicle will be used exclusively for rental | Button | D | |
| Consumers Certificate of Exemption Number | Text | D | |
| Sales Tax Registration Number | Text | D | |
| Inheritance_2 | Button | D | |
| Gift_2 | Button | D | |
| Divorce Decree | Button | D | |
| Transfer between a married couple | Button | D | |
| Other Exemption | Button | D | |
| Specify Reason | Text | D | |

## Section 10 — Repossession Declaration

| Field | Type | Class | Source / note |
|---|---|---|---|
| I certify that this motor vehicle was repossessed… | Button | D | |
| I certify that the certificate of title is lost or destroyed | Button | D | |
| Full Name of Applicant | Text | D ⚠ | unclear whether this belongs to Section 10 or 11; both sections carry an applicant name |
| Full Name of Applicant_2 | Text | D ⚠ | as above |
| Signature of Applicant | Signature | D | wet signature |
| Date of Applicant Signature | Text | D | signed by hand |
| Signature of Applicant 2 | Signature | D | wet signature |
| Date of Applicant Signature 2 | Text | D | signed by hand |

## Section 11 — Non-Use and Other Certifications

| Field | Type | Class | Source / note |
|---|---|---|---|
| The vehicle identified will not be operated on the streets and highways… | Button | D | applicant election |
| Other Non-Use/Certification | Button | D | applicant election |
| Other Non-Use/Certification: Explain | Text | D | applicant election |

## Section 12 — Application Attestment and Signatures

| Field | Type | Class | Source / note |
|---|---|---|---|
| Full Name of Applicant Owner | Text | B | `owner_name` |
| Full Name of Applicant CoOwner | Text | D | no co-owner concept |
| Signature of Applicant, Owner | Signature | D | wet signature |
| Date of Applicant, Owner Signature MM/DD/YYYY | Text | D ⚠ | this is the date of signing, not of generation — pre-filling it with `generated_at` would be wrong if signed later |
| Signature of Applicant, Co-Owner | Signature | D | wet signature |
| Date of Applicant, Co-Owner Signature MM/DD/YYYY | Text | D | wet signature |
| Signature of Notary Public | Signature | D | wet signature |

## Section 13 — Release of Spouse or Heirs Interest

Estate transfers only. Nothing in the schema models a deceased owner or heirs.

| Field | Type | Class | Source / note |
|---|---|---|---|
| Testate | Button | D | export value is `Yes`, not `On` |
| Intestate | Button | D | |
| When applicable, the heir(s) certifies that the certificate of title is lost or destroyed | Button | D | export value is `Yes`, not `On` |
| Spouse | Button | D | |
| Heir(s) | Button | D | |
| Spouse 2 | Button | D | |
| Co-Owner 2 | Button | D | |
| Heir 2 | Button | D | |
| Full Name of Spouse CoOwner or Heirs | Text | D | |
| Full Name of Spouse CoOwner or Heirs_2 | Text | D | |
| Signature of Spouse, Co-Owner, or Heir | Signature | D | wet signature |
| Date of Spouse, Co-Owner, or Heir Signature | Text | D | wet signature |
| Signature of Spouse, Co-Owner, or Heir 2 | Signature | D | wet signature |
| Date of Spouse, Co-Owner, Heir Signature 2 | Text | D | wet signature |
| Name of Deceased | Text | D | |
| Deceased Date | Text | D | |

---

## Summary

| Class | Fields | Share |
|---|---|---|
| **A** — already mapped | 17 | 8% |
| **B** — mappable now, no new data | 23 | 11% |
| **C** — in the DB, needs surfacing | 15 | 7% |
| **D** — data does not exist yet | 160 | 74% |
| **Total** | **215** | |

The D figure is less alarming than it looks. Roughly 90 of those fields belong to
scenarios this business does not handle — lienholders, estates, co-owners, dealer trade-ins,
vessels, sales-tax exemptions — and should stay permanently blank, exactly as they would on
a hand-completed form. A further ~20 are wet signatures and their dates.

**Realistic target: A + B + C = 55 fields, which covers every box a rebuilt-title
application actually needs**, plus perhaps 15 more from AI extraction of the driver's
licence (sex, DOB, DL number) and the bill of sale (sale price, date acquired, transfer
type).

## Recommended order

1. **B (23 fields), no new data required.** Owner contact block, odometer digits and status,
   model, title number. Config-only work.
2. **C (15 fields), surfacing existing columns.** Add `license_plate`, `previous_state`,
   `fuel_type` and `odometer_date` to `prepareFormData()`. ~~`fuel_type` alone lights up six
   boxes across Sections 2 and 3.~~ **Corrected 11 Aug:** `fuel_type` alone cannot light up
   the two hybrid boxes. NHTSA reports a hybrid's *primary* fuel as Gasoline — a 2018 Prius
   decodes as Gasoline / Electric / `Strong HEV (Hybrid Electric Vehicle)` — so a mapping
   driven by `fuel_type` would never tick them. `VINDecoderService` now also captures
   `Fuel Type - Secondary` and `Electrification Level` into `vehicles.vin_decoded_data`,
   and `App\Support\FuelType` classifies from all three. Done.
3. **Section 8 inspector block.** Small, high value, and it is the part of the packet the
   state actually scrutinises. Inspector name and signing date are mapped; the certifier
   type boxes and the signature image still wait on ambiguity 3 below.
4. **AI extraction.** Driver's licence and bill of sale, per S2 item 1.

## Resolved

- **Original vs Transfer** (5 Aug, Genesis, voice note). A **Florida title is an Original
  application; an out-of-state title is a Transfer.** The title number plays no part. This
  is the opposite of the rule previously inferred here. Mapped and tested.
- **Weight** (5 Aug, Genesis). Comes off the title, never from the VIN decoder's GVWR.
  Florida titles always carry one; out-of-state titles may not, and a blank box that staff
  complete by hand is explicitly acceptable. Mapped.
- **Assembled from Parts / Rebuilt.** Section 3 TYPE boxes now come from
  `vehicles.vehicle_type`, collected at intake, with `title_brand` left to the BRAND boxes
  it actually describes.

## Ambiguous — answered 13 Aug 2026

All eight went to the client in `docs/82040-client-questions-gdoc.html`. Genesis answered
on 13 Aug; the answers and their consequences are recorded in
`docs/82040-client-answers.md`. **Ebe has not answered**, so items 1, 3 and 6 below rest on
one person's reading.

Four are settled, one is done, and three are still open despite having a reply.

1. ~~**Mailing vs residential address.**~~ **Answered and mapped.** The driver licence
   address goes in both blocks. Applying it exposed a bug: we had been filling the street
   from the residential row and the city, state and zip from the mailing row, so neither
   row was a complete address. Both rows are now filled from the same keys. Open follow-up:
   intake asks for "Address", not the licence address we now print.
2. ~~**Request to print Certificate of Title.**~~ **Settled: leave blank** for the customer
   to tick on the day, which is what the code already does.
3. **Certifying inspector type** in Section 8 — **still open.** The reply, "it varies per
   inspection, out of state vessels only require this confirmation", says *when* the
   section applies rather than *which* of the five authorities signs it, and the
   licence/badge number came back empty. Blocks Section 8 here and all of Part B on the
   82042. Also worth confirming "vessels" was meant as "vehicles".
4. ~~**GVW.**~~ **Settled: leave blank.** Weight continues to come off the title.
5. ~~**5- vs 6-digit odometer.**~~ **Answered, needs building.** Inspectors record it on
   site, replacing the current model-year rule. Touches the inspection record, an
   inspector input, `prepareFormData()`, and the three field maps that consume
   `is_six_digit_odometer`.
6. **Section 7 dealer fields** — **still open.** Rapid Rebuilt does fill this section, but
   both numbers came back as "Per dealer information" rather than digits, and
   `settings.prvip_license_number` and `settings.tax_id` are both empty.
7. ~~**"Other"** (Section 2).~~ **Settled: leave blank.** The client does not know what it
   is for either.
8. ~~**"Full Name of Applicant" / "_2".**~~ **Settled: leave both blank.**
