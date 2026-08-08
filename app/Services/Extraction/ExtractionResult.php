<?php

namespace App\Services\Extraction;

/**
 * What one extractor read from one document.
 *
 * Deliberately free of Eloquent: an extractor produces this, and only
 * DocumentExtractionService decides how it is persisted.
 */
readonly class ExtractionResult
{
    /**
     * @param  array<string, string|null>  $fields  field_key => transcribed value, null when absent from the document
     * @param  array<string, mixed>  $raw  the provider's structured response verbatim, kept for the audit trail
     * @param  array<string, float>  $confidence  field_key => 0..1, only for fields the provider scored
     */
    public function __construct(
        public array $fields,
        public array $raw = [],
        public array $confidence = [],
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public string $provider = '',
        public string $model = '',
    ) {}

    public function value(string $fieldKey): ?string
    {
        return $this->fields[$fieldKey] ?? null;
    }

    public function confidenceFor(string $fieldKey): ?float
    {
        return $this->confidence[$fieldKey] ?? null;
    }
}
