<?php

namespace App\Services\Extraction;

use App\Models\UploadedFile;
use App\Services\Extraction\Contracts\ExtractsDocuments;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Usage;

/**
 * Reads a state salvage title into the fields the HSMV 82040 needs.
 *
 * Every field is nullable: a real title may genuinely lack a weight or a
 * plate, and a model forced to invent one is worse than a blank a human can
 * fill in. Nothing here is written anywhere — see ExtractionConfirmer.
 */
class SalvageTitleExtractor implements ExtractsDocuments
{
    public const DOCUMENT_TYPE = 'salvage_title';

    /**
     * The VIN alphabet excludes I, O and Q, so this pattern does real work
     * against a misread character rather than just counting to 17.
     */
    private const VIN_PATTERN = '^[A-HJ-NPR-Z0-9]{17}$';

    /**
     * field_key => [label for staff, description sent to the model].
     *
     * Each field is either already mapped in config/hsmv_forms.php or is an
     * 82040 field waiting on data; owner_name_on_title is the one exception
     * and exists only so the review screen can flag a title held in someone
     * else's name.
     */
    private const FIELDS = [
        'vin' => [
            'Vehicle Identification Number',
            'The 17-character VIN exactly as printed. Use only the characters shown; the VIN alphabet has no I, O or Q.',
        ],
        'title_number' => [
            'Title Number',
            'The title or certificate number issued by the state, digits and letters only.',
        ],
        'title_state' => [
            'Issuing State',
            'The two-letter USPS code of the state that issued this title, e.g. FL, GA, TX.',
        ],
        'title_brand' => [
            'Title Brand',
            'The brand printed on the title, such as salvage, rebuilt, flood, junk or clear. Lowercase, one word.',
        ],
        'year' => [
            'Model Year',
            'The four-digit vehicle model year.',
        ],
        'make' => [
            'Make',
            'The vehicle make as printed, e.g. HONDA, FORD.',
        ],
        'model' => [
            'Model',
            'The vehicle model as printed, e.g. ACCORD, F150.',
        ],
        'body_type' => [
            'Body Type',
            'The body type or style as printed, e.g. 4D SEDAN, PICKUP, UT.',
        ],
        'color' => [
            'Colour',
            'The vehicle colour as printed on the title.',
        ],
        'weight' => [
            'Weight',
            'The weight printed on the title, in pounds, digits only. Do not use gross vehicle weight rating or any figure not printed on this title.',
        ],
        'odometer_reading' => [
            'Odometer Reading',
            'The odometer reading printed on the title, digits only, no tenths and no separators.',
        ],
        'odometer_date' => [
            'Odometer Date Read',
            'The date the odometer was read, in YYYY-MM-DD format.',
        ],
        'odometer_status' => [
            'Odometer Status',
            'The odometer declaration: actual, not_actual, or exceeds_limits. Use actual when the title shows no discrepancy marking.',
        ],
        'previous_state' => [
            'Previous State',
            'The state the vehicle was previously titled in, two-letter USPS code, only if the title names one distinct from the issuing state.',
        ],
        'license_plate' => [
            'License Plate',
            'The license plate or tag number printed on the title.',
        ],
        'owner_name_on_title' => [
            'Owner Name on Title',
            'The full name of the owner exactly as printed on the title.',
        ],
    ];

    public function __construct(
        private readonly DocumentImageNormalizer $normalizer,
    ) {}

    public function documentType(): string
    {
        return self::DOCUMENT_TYPE;
    }

    public function schema(): ObjectSchema
    {
        $properties = [];

        foreach (self::FIELDS as $key => [, $description]) {
            $properties[] = new StringSchema(
                name: $key,
                description: $description,
                nullable: true,
                pattern: $key === 'vin' ? self::VIN_PATTERN : null,
            );
        }

        return new ObjectSchema(
            name: 'salvage_title',
            description: 'Fields transcribed from a vehicle salvage title.',
            properties: $properties,
            requiredFields: array_keys(self::FIELDS),
        );
    }

    /**
     * @return array<string, string>
     */
    public function fieldLabels(): array
    {
        return array_map(fn (array $field): string => $field[0], self::FIELDS);
    }

    public function extract(UploadedFile $file): ExtractionResult
    {
        $image = $this->normalizer->toImage($file);
        $provider = $this->provider();
        $model = $this->model();

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSchema($this->schema())
            ->withMaxTokens((int) config('extraction.max_tokens'))
            ->usingTemperature(config('extraction.temperature'))
            ->withClientOptions(['timeout' => (int) config('extraction.timeout')])
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($this->prompt(), [$image])
            ->asStructured();

        return $this->toResult($response->structured ?? [], $response->usage, $provider, $model);
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function toResult(
        array $structured,
        ?Usage $usage,
        string $provider,
        string $model
    ): ExtractionResult {
        $fields = [];

        foreach (array_keys(self::FIELDS) as $key) {
            $value = $structured[$key] ?? null;

            // A blank string is the model saying "nothing here" in a slightly
            // different way; both mean the field is absent.
            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            } elseif ($value !== null) {
                $value = (string) $value;
            }

            $fields[$key] = $value;
        }

        return new ExtractionResult(
            fields: $fields,
            raw: $structured,
            promptTokens: $usage?->promptTokens,
            completionTokens: $usage?->completionTokens,
            provider: $provider,
            model: $model,
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You transcribe United States vehicle titles for a rebuilt-title service.
        You are transcribing, not interpreting: copy what is printed.

        Rules:
        - Return null for any field that is not printed on this document or that you cannot read with certainty. Never guess, never infer from the make or model, and never carry a value over from another field.
        - Do not correct apparent errors on the document. If the printed VIN looks wrong, transcribe what is printed.
        - Strip labels, punctuation and units; return the value only.
        PROMPT;
    }

    private function prompt(): string
    {
        return 'Transcribe the fields of this vehicle salvage title.';
    }

    private function provider(): string
    {
        return config('extraction.documents.' . self::DOCUMENT_TYPE . '.provider')
            ?? config('extraction.default');
    }

    private function model(): string
    {
        return config('extraction.documents.' . self::DOCUMENT_TYPE . '.model')
            ?? config('extraction.model');
    }
}
