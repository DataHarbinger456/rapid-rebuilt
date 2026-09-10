<?php

/*
|--------------------------------------------------------------------------
| Florida HSMV Form Field Maps
|--------------------------------------------------------------------------
|
| Maps our internal data keys (produced by PDFGeneratorService::prepareFormData)
| onto the field names inside the official fillable HSMV PDFs.
|
| The keys of each 'fields' array are the EXACT field names inside the PDF.
| Get them with:
|
|     pdftk docs/forms/82040.pdf dump_data_fields_utf8
|
| Always use dump_data_fields_utf8, not dump_data_fields — the plain version
| HTML-escapes names (&#8217; for a curly apostrophe), and those escaped names
| will not match when filling.
|
| A value may be:
|   - a string: the data key to use, e.g. 'vin'
|   - an array of strings: those data keys joined with a space. Several HSMV
|     fields are a single box for what we store separately, e.g. the owner's
|     name on the 82040 is one field covering first, middle and last.
|   - a checkbox: ['checkbox' => 'is_rebuilt', 'on' => 'On'], where 'on' is
|     that button's exact export value, taken from its FieldStateOption lines
|     in the dump. Export values are case sensitive and differ per field, so
|     never assume 'Yes' or '1'.
|
| Empty or missing text values are skipped, leaving that box blank on the
| form. Checkboxes are always written; a false flag sets the box to Off.
|
*/

return [

    'hsmv_82040' => [

        // Application for Certificate of Motor Vehicle Title.
        'template' => 'docs/forms/82040.pdf',

        // Partial map. The remaining fields land as the AI extraction work
        // progresses.
        'fields' => [
            // Section 1: OWNER / APPLICANT INFORMATION.
            //
            // The apostrophes below are wildly inconsistent and every one is
            // verbatim from dump_data_fields_utf8: 'Owner s County' has none at
            // all, 'Owners Email' has none, the phone box has a CURLY one
            // (U+2019) and the city/state/zip boxes have PLAIN ASCII ones. A
            // mismatch here is silent — pdftk ignores the unknown name and the
            // box prints blank — which is what the field-name test guards.
            //
            // The mailing-address block and the separate residential
            // city/state/zip boxes stay unmapped: we store a single address and
            // the form has two blocks for it. Pending client confirmation.
            'Owner s County of Residence' => 'owner_county',
            'Owners Email Voluntary' => 'owner_email',
            'Owner’s Phone Number (Voluntary)' => 'owner_phone',
            'Owner\'s City' => 'owner_city',
            'Owner\'s State' => 'owner_state',
            'Owner\'s Zip Code' => 'owner_zip',

            // Section 2: MOTOR VEHICLE DESCRIPTION.
            'Vehicle Identification Number VIN' => 'vin',
            'Year' => 'year',
            'Make/Manufacturer' => 'make',
            'Model' => 'model',
            'Body' => 'body_type',
            'Color' => 'color',

            // The state that issued the title being surrendered. See
            // prepareFormData() for why this prefers previous_state.
            'Previous State of Issue' => 'previous_state_of_issue',

            // This box asks specifically for a FLORIDA title number, so it is
            // filled only when the surrendered title is a Florida one. An
            // out-of-state title has no Florida number yet, and printing its
            // Georgia number here would be a false statement on a sworn
            // application. See prepareFormData().
            'Florida Title Number' => 'florida_title_number',
            'License Plate Number' => 'license_plate',

            // Fuel. The form has a box per alternative fuel and none for
            // petrol or diesel, so an ordinary car ticks nothing in this group.
            // A hybrid cannot be identified from vehicles.fuel_type, which
            // reports Gasoline for one: see App\Support\FuelType.
            'Natural Gas Compressed' => ['checkbox' => 'is_cng', 'on' => 'On'],
            'Natural Gas Liquid' => ['checkbox' => 'is_lng', 'on' => 'On'],
            'Hybrid GasElectric' => ['checkbox' => 'is_hybrid_gas_electric', 'on' => 'On'],
            'Hybrid DieselElectric' => ['checkbox' => 'is_hybrid_diesel_electric', 'on' => 'On'],
            'Electric' => ['checkbox' => 'is_electric', 'on' => 'On'],

            // Weight is transcribed from the title, never derived from the VIN
            // decoder's GVWR — the form has a separate GVW box for that.
            // Out-of-state titles may not carry a weight, in which case this
            // stays blank for staff to complete by hand.
            'Weight' => 'weight',

            // One box on the form for the full name, so first and last are
            // joined. The PDF really does include the trailing description
            // text in its field name.
            'Owner’s Name as It Appears on Driver License (First, Full Middle/Maiden, & Last Name)' => [
                'owner_first_name',
                'owner_last_name',
            ],

            // THE ADDRESS BLOCKS. Client decision, 13 Aug: the address printed
            // here is the one on the owner's driver licence, and it goes in
            // both blocks.
            //
            // That answer also fixed a real bug. The form has two address rows,
            // and measuring the widget positions shows which box belongs to
            // which:
            //
            //   row top=232  Owners Mailing Address | Owner's City | State | Zip
            //   row top=255  Owner's Residential Street Address | Owner’s Residential City | State | Zip
            //
            // Before this change we filled the street from the RESIDENTIAL row
            // and the city, state and zip from the MAILING row, so a printed
            // form carried a street with no city on one line and a city with no
            // street on the next. Neither row was a complete address.
            //
            // Both rows are now filled from the same keys, which is what the
            // client asked for and leaves each row complete on its own.
            //
            // Apostrophes are verbatim and inconsistent: 'Owners Mailing
            // Address' has none, "Owner's City" is plain ASCII, and the three
            // Residential City/State/Zip boxes are curly (U+2019). Copy them
            // from the dump, never retype.
            'Owners Mailing Address' => 'owner_address',
            'Owner\'s Residential Street Address' => 'owner_address',
            'Owner’s Residential City' => 'owner_city',
            'Owner’s Residential State' => 'owner_state',
            'Owner’s Residential Zip Code' => 'owner_zip',

            // Application Type, top of page 1. Florida title = Original,
            // out-of-state title = Transfer (client, 5 Aug). They are two
            // independent checkboxes in the PDF rather than a radio group, so
            // both are written explicitly.
            'Original' => ['checkbox' => 'is_application_original', 'on' => 'On'],
            'Transfer' => ['checkbox' => 'is_application_transfer', 'on' => 'On'],

            // Section 3: BRANDS, USAGE AND TYPE.
            // TYPE boxes, driven by vehicles.vehicle_type. That enum's
            // mobile_home and other values have no box of their own here, so
            // they legitimately tick nothing in this group.
            'Rebuilt' => ['checkbox' => 'is_rebuilt', 'on' => 'On'],
            'Assembled from Parts' => ['checkbox' => 'is_assembled_from_parts', 'on' => 'On'],
            'Kit Car' => ['checkbox' => 'is_kit_car', 'on' => 'On'],
            'Custom' => ['checkbox' => 'is_custom', 'on' => 'On'],
            'Street Rod' => ['checkbox' => 'is_street_rod', 'on' => 'On'],
            'Replica' => ['checkbox' => 'is_replica', 'on' => 'On'],

            // BRAND box, driven by vehicles.title_brand.
            'Flood' => ['checkbox' => 'is_flood', 'on' => 'On'],

            // The form repeats an Electric box in Section 3. Same data, same
            // answer: a form with one ticked and not the other would look like
            // two contradictory declarations.
            'Electric_2' => ['checkbox' => 'is_electric', 'on' => 'On'],

            // Section 6: ODOMETER DECLARATION.
            //
            // The mileage is six separate boxes, one per decimal place, filled
            // right to left. These names are verbatim from
            // dump_data_fields_utf8 including the inconsistent spacing: the
            // hundred-thousands box really is '100, 000 Place' with a space
            // after the comma while the others have none.
            '100, 000 Place' => 'odometer_digit_100000',
            '10,000 Place' => 'odometer_digit_10000',
            '1,000 Place' => 'odometer_digit_1000',
            '100 Place' => 'odometer_digit_100',
            '10 Place' => 'odometer_digit_10',
            'Ones Place' => 'odometer_digit_1',

            // Date the odometer was read, three boxes.
            'Date Read MM' => 'odometer_date_mm',
            'Date Read DD' => 'odometer_date_dd',
            'Date Read YYYY' => 'odometer_date_yyyy',

            // The 1/2/3 declaration, driven by vehicles.odometer_status.
            // Exactly one is ever ticked and an unknown status ticks none.
            // Note the double space in the first name and the missing full
            // stops after the numbers, both verbatim from the dump.
            '1  REFLECTS ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_actual', 'on' => 'On'],
            '2 IS NOT THE ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_not_actual', 'on' => 'On'],
            '3 IS IN EXCESS OF ITS MECHANICAL LIMITS' => [
                'checkbox' => 'is_odometer_exceeds_limits',
                'on' => 'On',
            ],

            // How many digits the instrument cluster shows. The title never
            // states it and the reading cannot imply it, but the model year
            // can: see prepareFormData().
            //
            // Both boxes are mapped now that inspectors record what the cluster
            // actually shows (client, 13 Aug). Before that only the 6-digit box
            // was mapped, because the model-year fallback can justify "six" but
            // never "five": a pre-1995 car might have either. With no
            // inspection on file that fallback still applies and the 5-digit
            // box stays blank.
            '6-digit odometer now reads' => ['checkbox' => 'is_six_digit_odometer', 'on' => 'On'],
            '5-digit odometer now reads' => ['checkbox' => 'is_five_digit_odometer', 'on' => 'On'],

            // Section 7: DEALER SALES TAX REPORT, page 2. Filled only when the
            // case belongs to a dealer; a private customer leaves the whole
            // block blank, which is what the heading's "(If applicable)" asks
            // for. Both keys resolve to null for a non-dealer, and mapFields()
            // omits an empty text value, so the boxes are never written.
            //
            // These are the DEALER's own numbers, off their user record. They
            // are not Setting::get('prvip_license_number'), which is one
            // company-wide value for a box that differs per dealer.
            //
            // 'Date of Sale' and 'Amount of Tax' sit in the same row and stay
            // unmapped: neither is stored, and both are per-sale facts rather
            // than dealer facts.
            //
            // Not to be confused with 'Sales Tax Registration Number' further
            // down page 2, which belongs to the rental-use exemption claim and
            // is a different declaration entirely.
            'Florida Sales Tax Registration Number' => 'dealer_sales_tax_number',
            'Dealer License Number' => 'dealer_license',

            // Section 8: MOTOR VEHICLE IDENTIFICATION NUMBER VERIFICATION.
            // The VIN is restated here for the inspector's certification. The
            // rest of this section — inspector name, date and signature — waits
            // on the client confirming which of the five certifier types their
            // inspectors are.
            'Vehicle Identification Number VIN_2' => 'vin',
            'Name Certifying Inspector' => 'inspector_name',
            'Date of Inspector Signature' => 'inspector_signed_date',

            // Section 12: APPLICATION ATTESTMENT AND SIGNATURES. The printed
            // name only; the signature and its date are wet-signed on the day.
            'Full Name of Applicant Owner' => 'owner_name',
        ],
    ],

    'hsmv_82042' => [

        // Vehicle Identification Number and Odometer Verification.
        'template' => 'docs/forms/82042.pdf',

        // Part A only. Every key here is one the 82040 already derives, so
        // this map adds no keys to prepareFormData(): see docs/82042-coverage.md.
        //
        // Part B, the certifier's VIN verification, is entirely unmapped. It is
        // the same open question as the 82040's Section 8 — which of the five
        // authorities the client's inspectors are — and until that is answered,
        // naming one on a form sworn under penalty of perjury would be a false
        // certification. Its three certifier-type buttons also export 'Yes'
        // rather than 'On', which is pinned by test so the mapping cannot get
        // it wrong later.
        'fields' => [

            // PART A: VEHICLE IDENTIFICATION.
            'Vehicle Identification Number' => 'vin',
            'Year' => 'year',
            'Make' => 'make',
            'Color' => 'color',
            'Body Type' => 'body_type',
            'Previous State Vehicle Titled In' => 'previous_state_of_issue',

            // PART A: ODOMETER DECLARATION.
            //
            // The digits are split one box per place, most significant first,
            // exactly as on the 82040.
            'Hundred thousands place that the odometer number now reads' => 'odometer_digit_100000',
            'Ten thousands place that the odometer number now reads' => 'odometer_digit_10000',
            'Thousands place that the odometer number now reads' => 'odometer_digit_1000',
            'Hundreds place that the odometer number now reads' => 'odometer_digit_100',
            'Tens place that the odometer number now reads' => 'odometer_digit_10',
            'Ones place that the odometer number now reads' => 'odometer_digit_1',

            'Two-digit month of odometer reading' => 'odometer_date_mm',
            'Two-digit day of odometer reading' => 'odometer_date_dd',
            'Four-digit year of odometer reading' => 'odometer_date_yyyy',

            // Both boxes, since inspectors now record what the cluster shows.
            // With no inspection on file the model-year fallback applies, which
            // can assert six digits but never five, so the 5-digit box stays
            // blank rather than guessing. Same rule on all three forms.
            'Select if 6 digit odometer reading' => ['checkbox' => 'is_six_digit_odometer', 'on' => 'On'],
            'Select if 5 digit odometer reading' => ['checkbox' => 'is_five_digit_odometer', 'on' => 'On'],

            // WARNING: the 82040 and the 82042 number these three differently.
            //
            //             82040                        82042
            //   1   reflects actual mileage      reflects actual mileage
            //   2   is NOT the actual mileage    is IN EXCESS of its limits
            //   3   is IN EXCESS of its limits   is NOT the actual mileage
            //
            // Options 2 and 3 are swapped between the forms. Each key below is
            // matched to the SENTENCE in its field name, never to the leading
            // digit; mapping by number would swear that a rolled-over odometer
            // had been tampered with, or the reverse.
            '1. reflects ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_actual', 'on' => 'On'],
            '2. is IN EXCESS OF ITS MECHANICAL LIMITS' => ['checkbox' => 'is_odometer_exceeds_limits', 'on' => 'On'],
            '3. is NOT THE ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_not_actual', 'on' => 'On'],

            // PART A: SIGNATURES. The printed owner name only; both signatures
            // are wet, and the seller is someone we never store.
            'Owner/Purchaser Name' => 'owner_name',
        ],
    ],

    'hsmv_82050' => [

        // Notice of Sale and/or Bill of Sale for a Motor Vehicle, Mobile Home,
        // Off-Highway Vehicle or Vessel.
        'template' => 'docs/forms/82050.pdf',

        // WARNING: the field names in this form are auto-generated and most of
        // them lie. Acrobat named each box after whatever text sat nearest when
        // the form was authored, so the Certificate of Title Number box is
        // called 'Iwe do hereby sell or have sold and delivered...' and the six
        // odometer digits are called Text1, 'Federal and State law requires...',
        // undefined_2, 'Failure to', undefined_3 and 'xx'.
        //
        // Each of those was decoded by reading the widget rectangles out of the
        // PDF and sorting by position on the page, and that evidence is recorded
        // above the mapping it justifies. Coordinates are points from the
        // top-left of the single page. Never map one of these from its name,
        // and never copy a name from another form: see docs/82050-coverage.md.
        //
        // Section 6 of the 82040 and Part A of the 82042 ask these same
        // questions, so every key here is one prepareFormData() already derives.
        'fields' => [

            // SECTION 1: VEHICLE DESCRIPTION.
            'Year' => 'year',
            'MakeManufacturer' => 'make',
            'Body Type' => 'body_type',
            'Model' => 'model',
            'Color' => 'color',

            // Certificate of Title Number. Leftmost box (left=46) of the
            // three-box row at top=206, whose printed labels read
            // "Certificate of Title Number | Current Title Issue Date |
            // Vehicle/Vessel Identification Number"; the other two boxes in that
            // row sit at left=197 and left=329 and are named honestly, which
            // leaves this one as the title number by elimination.
            //
            // Unlike the 82040's box this one is not state-qualified, so it
            // takes the title number whichever state issued it.
            'Iwe do hereby sell or have sold and delivered the above described motor vehicle mobile home' => 'title_number',

            'VehicleVessel Identification Number' => 'vin',

            // SECTION 2: ODOMETER DISCLOSURE STATEMENT.
            //
            // Both boxes, since inspectors now record what the cluster shows.
            // With no inspection on file the model-year fallback applies, which
            // can assert six digits but never five, so the 5-digit box stays
            // blank rather than guessing. Same rule on all three forms.
            '6 DIGIT ODOMETER NOW READS' => ['checkbox' => 'is_six_digit_odometer', 'on' => 'On'],
            '5 DIGIT OR' => ['checkbox' => 'is_five_digit_odometer', 'on' => 'On'],

            // The six digit boxes are one row at top=383, in this order by left
            // edge: 437, 456, 474, 497, 515, 533. The gap between the third and
            // fourth is 11pt where every other gap is 6pt, and that wider gap is
            // where the form prints the thousands comma. So the row splits
            // hundred-thousands / ten-thousands / thousands, comma, then
            // hundreds / tens / ones: most significant first.
            'Text1' => 'odometer_digit_100000',
            'Federal and State law requires that you state the mileage in connection with the transfer of ownership' => 'odometer_digit_10000',
            'undefined_2' => 'odometer_digit_1000',
            'Failure to' => 'odometer_digit_100',
            'undefined_3' => 'odometer_digit_10',
            'xx' => 'odometer_digit_1',

            // The three date boxes are the next row down at top=397, by left
            // edge: 181, 219, 256. The printed rule above them reads
            // "DATE READ ____/____/____", so the order is month, day, year.
            'undefined_4' => 'odometer_date_mm',
            'undefined_5' => 'odometer_date_dd',
            'AND WE HEREBY CERTIFY THAT TO THE BEST OF OUR KNOWLEDGE' => 'odometer_date_yyyy',

            // The declarations. This form and the 82042 agree with each other
            // and disagree with the 82040, which swaps options 2 and 3:
            //
            //             82040                      82042 and 82050
            //   1   reflects actual mileage     reflects actual mileage
            //   2   is NOT the actual mileage   is IN EXCESS of its limits
            //   3   is IN EXCESS of its limits  is NOT the actual mileage
            //
            // Every key below is matched to the SENTENCE in its field name,
            // never to the leading digit. All three forms also word that same
            // sentence differently, so these names cannot be copied from the
            // other maps even where the meaning is identical.
            '1 REFLECTS THE ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_actual', 'on' => 'On'],
            '2 IS IN EXCESS OF ITS MECHANICAL LIMITS' => ['checkbox' => 'is_odometer_exceeds_limits', 'on' => 'On'],
            '3 IS NOT THE ACTUAL MILEAGE' => ['checkbox' => 'is_odometer_not_actual', 'on' => 'On'],

            // TODO: eight fields are blocked on who signs this form.
            //
            // The 82050 is completed by the SELLER. For a rebuilt title case
            // our customer is the purchaser: they bought a salvage vehicle,
            // which is what config/documents.php already calls the bill of sale
            // we collect from them. But if the customer already holds that
            // document, generating a second one that only the original seller
            // can sign is of little use, which suggests the packet's copy may
            // be for selling the rebuilt vehicle afterwards, with the customer
            // as the seller instead.
            //
            // The two readings are mirror images: under one, these eight take
            // owner_name and the owner address keys; under the other, most of
            // them are a party we never store. Mapping either way now would put
            // the customer's name on the wrong side of a document signed under
            // penalty of perjury, so all eight stay out of the map until the
            // client answers. This is ambiguity 1 in docs/82050-coverage.md.
            //
            // Names are verbatim from dump_data_fields_utf8. The two header
            // buttons contain a DOUBLE SPACE, in 'sections 1  3' and in
            // 'when applicable  3', which has to survive copy and paste or the
            // mapping will silently miss and the box will print blank.
            //
            //   'Notice of Sale Seller must complete sections 1  3 The purchasers signature in section 3 is optional'
            //   'Bill of Sale Seller and purchaser must complete sections 1 2 when applicable  3'
            //   'Print Names of Purchasers'
            //   'Address City State Zip Code'
            //   'Sellers Printed Name'
            //   'Date'
            //   'Sellers Address City State Zip Code'
            //   'Purchasers Printed Name'
            //
            // Three of the eight need nothing else once the answer lands:
            // Print Names of Purchasers and Purchasers Printed Name are
            // owner_name, and Address City State Zip Code is one box for four
            // keys, which the array form already handles:
            // ['owner_address', 'owner_city', 'owner_state', 'owner_zip'].
        ],
    ],

    'hsmv_84490' => [

        // Statement of Builder.
        'template' => 'docs/forms/84490.pdf',

        // STEP 1 ONLY: the 20 descriptive fields. See docs/84490-coverage.md.
        //
        // This form is a builder's DECLARATION, sworn twice, and its back half
        // belongs to the state. Section IV's perjury clause covers the whole
        // document — "I declare that I have read the foregoing document and
        // that the facts provided herein are true" — so everything printed
        // above the applicant's signature becomes their sworn statement.
        //
        // What is filled here is descriptive fact already on file: the
        // vehicle, the title, and the inspector's own recorded name and date.
        // 114 of the 134 fields stay deliberately blank, and each has a reason
        // pinned by a test in HSMV84490FieldMapTest:
        //
        //   Section III's two boxes swear to USAGE INTENT and EQUIPMENT under
        //   s.320.0863. is_custom/is_street_rod describe a vehicle TYPE and are
        //   the obvious wrong mapping — ticking one would be a false
        //   attestation only the owner can make.
        //
        //   The Section I initials boxes are a personal attestation.
        //
        //   Section IV's applicant block is blocked on ambiguity 1: whether
        //   the customer or Rapid Rebuilt signs as builder. The owner_* keys
        //   are only correct in the first case.
        //
        //   The parts table (30 fields) is blocked on ambiguity 6:
        //   components.condition stores new/used/repaired/rebuilt/original,
        //   the form offers New/Used/Repaired/Aftermarket/Homemade, and two
        //   values on each side have no counterpart.
        //
        //   The page-4 Yes/No matrix (22 buttons) is blocked on ambiguity 3:
        //   which rows a PRVIP inspector may swear to.
        //
        //   Audit # and Region # are ours and unambiguous, but they sit inside
        //   the Section V office block; they land with step 3 rather than
        //   ahead of the question about who completes that block.
        //
        //   CoAgy waits on settings.prvip_license_number, which is empty.
        //
        // TRAP: field names in this PDF cannot be retyped by hand. 'Motorcycly'
        // is misspelled in Section I, 'Source/VIN 2' lacks the '#' its four
        // siblings have, 'Check Box28' and 'Check Box 28' differ by one space
        // and are different fields, and numbers 19-23 do not exist. Copy every
        // name from docs/forms/84490-fields.txt.
        //
        // TRAP: this form mixes 'On' and 'Yes' exports on the same page. The
        // three buttons mapped below export 'On', which is checkboxValue()'s
        // default, but the 25 parts-table buttons export 'Yes' and five of the
        // matrix buttons export 'On' where their neighbours export 'Yes'. Both
        // sets are pinned by test so the next pass cannot guess.
        'fields' => [

            // HEADER: type of build. OTHER is unmapped — no key is derived for
            // it, and the free-text description beside it has no source, so a
            // ticked OTHER with no description says less than a blank pair.
            'REBUILT' => ['checkbox' => 'is_rebuilt', 'on' => 'On'],
            'ASPT' => ['checkbox' => 'is_assembled_from_parts', 'on' => 'On'],
            'KIT CAR' => ['checkbox' => 'is_kit_car', 'on' => 'On'],

            // SECTION I: description.
            //
            // The three vehicle-class buttons (Motor Vehicle / Motorcycle /
            // Mobile Home) are unmapped: no vehicle-class column exists
            // anywhere in the schema. Length and the second title pair have no
            // source either.
            'Year' => 'year',
            'Make' => 'make',
            'Identification Number' => 'vin',
            'Color' => 'color',
            'Body' => 'body_type',

            // NOT the same rule as the 82040's Section 2. That box is labelled
            // "Florida Title Number" and so takes florida_title_number, which
            // is null for an out-of-state title. This box asks for the title
            // number full stop, so the surrendered title's own number belongs
            // here whatever state issued it.
            'Title Number' => 'title_number',
            'Title State' => 'title_state',

            // SECTION V: the descriptive block on page 3.
            //
            // The form asks for the same vehicle a second time under different
            // field names. Pre-filling it helps whoever completes the rest of
            // the block, and nothing here is an attestation.
            'VIN' => 'vin',
            'Title #1' => 'title_number',
            'Title State #1' => 'title_state',
            'Year #1' => 'year',
            'Make #1' => 'make',
            'Body #1' => 'body_type',
            'Color #1' => 'color',

            // One plain box, unlike the per-digit rows on the 82040, 82042 and
            // 82050. This is the first use of the whole-number odometer key.
            'Odometer' => 'odometer',

            // The PRVIP inspector's line. The signature above it is wet, but a
            // printed name and a signing date are recorded facts rather than
            // an attestation, and both keys already serve the 82040's
            // Section 8. A case with no inspection leaves both blank rather
            // than claiming an inspection happened.
            'Print Name of PRVIP Inspector' => 'inspector_name',
            'Date_4' => 'inspector_signed_date',

            // The Section V office-use pair, on page 3. Our own reference
            // numbers, recorded on the inspection when it starts.
            //
            // TRAP: the page-4 Compliance Examiner block has its own box named
            // 'Region', one character from this one. That box belongs to a
            // state officer and stays blank; putting our number in it would
            // pass every value assertion while filling in someone else's
            // block. HSMV84490FieldMapTest pins both.
            //
            // region_number is dropped by prepareFormData() when it holds the
            // 'FL' fallback rather than a real region number.
            'Audit #' => 'audit_number',
            'Region #' => 'region_number',
        ],
    ],

];
