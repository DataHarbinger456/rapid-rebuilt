<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Catalogue
    |--------------------------------------------------------------------------
    |
    | Metadata for every customer-supplied document type. Requiredness is
    | deliberately NOT here: whether a document is required varies per service
    | type, and lives under 'requirements' below.
    |
    | Uploads are validated against this catalogue, not against a case's
    | requirement list — narrowing a service type's list must never start
    | rejecting a document a customer could legitimately send.
    |
    */

    'types' => [
        'salvage_title' => [
            'label' => 'Salvage Title',
            'description' => 'Original salvage title or certificate of destruction',
            'accept' => 'image/*,.pdf',
        ],
        'bill_of_sale' => [
            'label' => 'Bill of Sale',
            'description' => 'Bill of sale from when you purchased the vehicle',
            'accept' => 'image/*,.pdf',
        ],
        'owner_id_front' => [
            'label' => "Driver's License - Front",
            'description' => 'Front of the valid government-issued photo ID of the owner',
            'accept' => 'image/*,.pdf',
        ],
        'owner_id_back' => [
            'label' => "Driver's License - Back",
            'description' => 'Back of the valid government-issued photo ID of the owner',
            'accept' => 'image/*,.pdf',
        ],
        'insurance_card' => [
            'label' => 'Insurance Card',
            'description' => 'Current insurance card for the vehicle',
            'accept' => 'image/*,.pdf',
        ],
        'damage_photos' => [
            'label' => 'Damage Photos',
            'description' => 'Photos showing the vehicle before repair (damaged state)',
            'accept' => 'image/*',
            'multiple' => true,
        ],
        'repair_photos' => [
            'label' => 'Repair Photos',
            'description' => 'Photos showing the vehicle after repair',
            'accept' => 'image/*',
            'multiple' => true,
        ],
        'component_receipts' => [
            'label' => 'Component Receipts',
            'description' => 'Receipts for major components purchased for repair',
            'accept' => 'image/*,.pdf',
            'multiple' => true,
        ],
        'insurance_estimate' => [
            'label' => 'Insurance Estimate/Report',
            'description' => 'Insurance company estimate or damage report',
            'accept' => 'image/*,.pdf',
        ],
        'power_of_attorney' => [
            'label' => 'Power of Attorney',
            'description' => 'If someone else will be handling the title application',
            'accept' => 'image/*,.pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirements Per Service Type
    |--------------------------------------------------------------------------
    |
    | Keys match UserCase::SERVICE_TYPES. A case with a null or unknown
    | service_type resolves to 'default', which reproduces the single
    | hardcoded list this app used before requirements became case-aware.
    |
    | Every entry here must name a type present in the catalogue above.
    |
    */

    'requirements' => [

        'default' => [
            'required' => [
                'salvage_title',
                'bill_of_sale',
                'owner_id_front',
                'owner_id_back',
                'insurance_card',
                'damage_photos',
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'insurance_estimate',
                'power_of_attorney',
            ],
        ],

        'rebuilt_title' => [
            'required' => [
                'salvage_title',
                'bill_of_sale',
                'owner_id_front',
                'owner_id_back',
                'insurance_card',
                'damage_photos',
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'insurance_estimate',
                'power_of_attorney',
            ],
        ],

        'salvage_inspection' => [
            'required' => [
                'salvage_title',
                'bill_of_sale',
                'owner_id_front',
                'owner_id_back',
                'insurance_card',
                'damage_photos',
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'insurance_estimate',
                'power_of_attorney',
            ],
        ],

        'dealer_package' => [
            'required' => [
                'salvage_title',
                'bill_of_sale',
                'owner_id_front',
                'owner_id_back',
                'insurance_card',
                'damage_photos',
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'insurance_estimate',
                'power_of_attorney',
            ],
        ],

        'expedited' => [
            'required' => [
                'salvage_title',
                'bill_of_sale',
                'owner_id_front',
                'owner_id_back',
                'insurance_card',
                'damage_photos',
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'insurance_estimate',
                'power_of_attorney',
            ],
        ],

        // A re-inspection follows an original case whose title, ID and
        // insurance paperwork is already on file; only the repair evidence
        // has to be sent again.
        'reinspection' => [
            'required' => [
                'repair_photos',
                'component_receipts',
            ],
            'optional' => [
                'damage_photos',
                'insurance_estimate',
            ],
        ],

    ],

];
