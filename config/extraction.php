<?php

use App\Services\Extraction\SalvageTitleExtractor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Provider and Model
    |--------------------------------------------------------------------------
    |
    | Every extractor resolves its provider and model from here rather than
    | hardcoding one, so swapping providers is an env change. The provider's
    | API key is read by config/prism.php from that provider's own env var.
    |
    */

    'default' => env('EXTRACTION_PROVIDER', 'anthropic'),

    'model' => env('EXTRACTION_MODEL', 'claude-sonnet-4-5'),

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    |
    | Maps an uploaded_files.document_type to the class that knows how to read
    | it. A null provider or model falls back to the defaults above; the
    | override exists because a driver's licence may warrant a cheaper model
    | than a salvage title.
    |
    */

    'documents' => [
        'salvage_title' => [
            'extractor' => SalvageTitleExtractor::class,
            'provider' => env('EXTRACTION_SALVAGE_TITLE_PROVIDER'),
            'model' => env('EXTRACTION_SALVAGE_TITLE_MODEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Limits
    |--------------------------------------------------------------------------
    |
    | temperature is deliberately not configurable: transcribing a state title
    | must not be creative.
    |
    */

    'max_tokens' => (int) env('EXTRACTION_MAX_TOKENS', 2048),

    'temperature' => 0,

    'timeout' => (int) env('EXTRACTION_TIMEOUT', 60),

];
