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

            // Note the plain apostrophe here versus the curly one above.
            // The form is inconsistent; both spellings are verbatim from the
            // PDF. There is also an 'Owners Mailing Address' box, unmapped
            // until we decide whether it should mirror this or be its own
            // input.
            'Owner\'s Residential Street Address' => 'owner_address',

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
            // can: see prepareFormData(). Ticked only from 1995 onwards.
            //
            // '5-digit odometer now reads' is deliberately left unmapped. It is
            // only correct for genuinely old vehicles, where the year alone is
            // not enough to justify a sworn statement, so that box is always
            // left for a human.
            '6-digit odometer now reads' => ['checkbox' => 'is_six_digit_odometer', 'on' => 'On'],

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

            // 'Select if 5 digit odometer reading' is left unmapped for the
            // same reason as on the 82040: only a human can swear to it.
            'Select if 6 digit odometer reading' => ['checkbox' => 'is_six_digit_odometer', 'on' => 'On'],

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

];
