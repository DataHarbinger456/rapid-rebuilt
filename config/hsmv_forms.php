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
        ],
    ],

];
