<?php

namespace App\Services\Extraction;

use App\Models\DocumentExtraction;
use App\Services\VINDecoderService;
use App\Support\Vin;
use Throwable;

/**
 * Cross-checks the VIN a vision model read against what the VIN itself decodes
 * to, so a misread character is caught by disagreeing with the vehicle.
 *
 * Independent of the ISO 3779 check digit and strictly stronger in one place:
 * on sample image 1 the model read the year as 2024 where the title said 2025,
 * which the checksum cannot see but the decoder does.
 *
 * Advisory only. Confirmation is never blocked, and a decoder that is down
 * must degrade to a quiet notice rather than an alarming one.
 */
class VinDecodeCheck
{
    public const MATCHED = 'matched';
    public const MISMATCHED = 'mismatched';
    public const UNAVAILABLE = 'unavailable';

    /**
     * Titles abbreviate the make ("TOYT", "CHEV", "HOND", "INFI") while the
     * decoder spells it out ("TOYOTA", "CHEVROLET", "HONDA", "INFINITI"), so
     * makes are compared on a short prefix.
     *
     * Three characters covers every abbreviation in the sample set; four would
     * wrongly split TOYT from TOYOTA. It does mean Mercury and Mercedes both
     * reduce to MER, which fails safe: no flag rather than a false one. A
     * cross-check that cries wolf gets ignored, and then it protects nothing.
     */
    private const MAKE_PREFIX = 3;

    /**
     * Titles frequently carry a body code in the model column ("4D", "UT",
     * "TK", "SEN") rather than a model name. Comparing those to "Explorer"
     * would flag almost every row, so anything this short is not compared.
     */
    private const MODEL_MIN_LENGTH = 4;

    /**
     * Longer body-style words that appear in the model column and are not
     * model names.
     */
    private const BODY_WORDS = [
        'SEDAN', 'COUPE', 'WAGON', 'TRUCK', 'PICKUP', 'CONVERTIBLE',
        'HATCHBACK', 'UTILITY', 'VAN', 'SUV',
    ];

    public function __construct(
        private readonly VINDecoderService $decoder,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     decoded: array{year: ?int, make: ?string, model: ?string},
     *     mismatches: array<string, array{extracted: ?string, decoded: string}>
     * }|null  null when there is no well-formed VIN to decode
     */
    public function for(DocumentExtraction $extraction): ?array
    {
        $values = $extraction->fields
            ->mapWithKeys(fn ($f): array => [$f->field_key => $f->confirmed_value ?? $f->ai_value])
            ->all();

        $vin = $values['vin'] ?? null;

        // Nothing to decode. A malformed VIN already has its own warning; a
        // second notice saying the decoder could not read it adds no
        // information.
        if ($vin === null || ! Vin::isWellFormed($vin)) {
            return null;
        }

        try {
            $decoded = $this->decoder->decode($vin);
        } catch (Throwable) {
            // decode() swallows its own exceptions, but a cache or transport
            // failure must not take the review screen down with it.
            $decoded = null;
        }

        $year = isset($decoded['year']) && $decoded['year'] > 0 ? (int) $decoded['year'] : null;
        $make = $this->clean($decoded['make'] ?? null);
        $model = $this->clean($decoded['model'] ?? null);

        // The decoder is down, or does not recognise this VIN. Either way it
        // has told us nothing about the vehicle.
        if ($year === null && $make === null && $model === null) {
            return [
                'status' => self::UNAVAILABLE,
                'decoded' => ['year' => null, 'make' => null, 'model' => null],
                'mismatches' => [],
            ];
        }

        $mismatches = [];

        if ($year !== null && $this->yearDiffers($values['year'] ?? null, $year)) {
            $mismatches['year'] = ['extracted' => $values['year'] ?? null, 'decoded' => (string) $year];
        }

        if ($make !== null && $this->makeDiffers($values['make'] ?? null, $make)) {
            $mismatches['make'] = ['extracted' => $values['make'] ?? null, 'decoded' => $make];
        }

        if ($model !== null && $this->modelDiffers($values['model'] ?? null, $model)) {
            $mismatches['model'] = ['extracted' => $values['model'] ?? null, 'decoded' => $model];
        }

        return [
            'status' => $mismatches === [] ? self::MATCHED : self::MISMATCHED,
            'decoded' => ['year' => $year, 'make' => $make, 'model' => $model],
            'mismatches' => $mismatches,
        ];
    }

    /**
     * Years are unambiguous on both sides, so they compare exactly.
     */
    private function yearDiffers(?string $extracted, int $decoded): bool
    {
        $extracted = $this->digits($extracted);

        return $extracted !== null && (int) $extracted !== $decoded;
    }

    private function makeDiffers(?string $extracted, string $decoded): bool
    {
        $a = $this->letters($extracted);
        $b = $this->letters($decoded);

        if ($a === null || $b === null) {
            return false;
        }

        return substr($a, 0, self::MAKE_PREFIX) !== substr($b, 0, self::MAKE_PREFIX);
    }

    /**
     * Models compare by containment: a title reading "ACCORD SEDAN TOURING"
     * agrees with a decoder reading "Accord".
     */
    private function modelDiffers(?string $extracted, string $decoded): bool
    {
        $a = $this->alphanumeric($extracted);
        $b = $this->alphanumeric($decoded);

        if ($a === null || $b === null) {
            return false;
        }

        // Too short, or a body style rather than a model: not comparable.
        if (strlen($a) < self::MODEL_MIN_LENGTH || in_array($a, self::BODY_WORDS, true)) {
            return false;
        }

        if (strlen($b) < self::MODEL_MIN_LENGTH) {
            return false;
        }

        return ! str_contains($a, $b) && ! str_contains($b, $a);
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function digits(?string $value): ?string
    {
        $value = preg_replace('/[^0-9]/', '', (string) $value);

        return $value === '' ? null : $value;
    }

    private function letters(?string $value): ?string
    {
        $value = preg_replace('/[^A-Z]/', '', strtoupper((string) $value));

        return $value === '' ? null : $value;
    }

    private function alphanumeric(?string $value): ?string
    {
        $value = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));

        return $value === '' ? null : $value;
    }
}
