<?php

namespace App\Services\Extraction;

use App\Models\CaseTimeline;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The only thing in the extraction pipeline that writes to a vehicle.
 *
 * It is never called automatically. Values reach the vehicle — and therefore
 * the HSMV 82040 — only once a member of staff has confirmed or corrected
 * every field on the review screen.
 */
class ExtractionConfirmer
{
    /**
     * field_key => vehicles column. Keys absent from this map are read for
     * cross-checking only; owner_name_on_title exists so the review screen can
     * flag a title held in someone else's name, and has no column to land in.
     */
    private const COLUMN_MAP = [
        'vin' => 'vin',
        'title_number' => 'title_number',
        'title_state' => 'title_state',
        'title_brand' => 'title_brand',
        'year' => 'year',
        'make' => 'make',
        'model' => 'model',
        'body_type' => 'body_type',
        'color' => 'color',
        'weight' => 'weight',
        'odometer_reading' => 'odometer_reading',
        'odometer_date' => 'odometer_date',
        'odometer_status' => 'odometer_status',
        'previous_state' => 'previous_state',
        'license_plate' => 'license_plate',
    ];

    private const INTEGER_FIELDS = ['year', 'weight', 'odometer_reading'];

    private const DATE_FIELDS = ['odometer_date'];

    private const UPPERCASE_FIELDS = ['vin', 'title_state', 'previous_state', 'license_plate'];

    /**
     * Columns the vehicles table will not accept a row without.
     */
    private const REQUIRED_FOR_NEW_VEHICLE = ['vin', 'year', 'make', 'model'];

    /**
     * Write every confirmed or corrected field to the case's vehicle.
     *
     * Rejected fields leave their column untouched: "the title does not show
     * this" is not the same statement as "this is blank".
     */
    public function apply(DocumentExtraction $extraction, User $staff): Vehicle
    {
        $extraction->loadMissing('fields');

        // A superseded attempt has been replaced by a newer read of the same
        // document. Applying it would push stale values onto the vehicle —
        // easily done with two browser tabs open.
        if ($extraction->status !== DocumentExtraction::STATUS_COMPLETED) {
            throw new RuntimeException(
                'Only a completed extraction can be applied; this one is '
                . $extraction->status . '.'
            );
        }

        if ($extraction->fields->isEmpty()) {
            throw new RuntimeException('This extraction has no fields to apply.');
        }

        $pending = $extraction->fields
            ->where('status', DocumentExtractionField::STATUS_PENDING);

        if ($pending->isNotEmpty()) {
            throw new RuntimeException(
                'Every field must be confirmed, corrected or rejected first. Still pending: '
                . $pending->pluck('field_key')->implode(', ')
            );
        }

        $vehicle = Vehicle::firstOrNew(['user_case_id' => $extraction->user_case_id]);
        $attributes = [];

        foreach ($extraction->fields as $field) {
            $column = self::COLUMN_MAP[$field->field_key] ?? null;
            $value = $field->accepted_value;

            if ($column === null || $value === null) {
                continue;
            }

            $attributes[$column] = $this->cast($field->field_key, $value);
        }

        // vehicles.vin, year, make and model are NOT NULL. A case normally
        // already has a vehicle from intake; if it does not, the document must
        // supply all four before a row can exist at all.
        if (! $vehicle->exists) {
            $missing = array_diff(self::REQUIRED_FOR_NEW_VEHICLE, array_keys($attributes));

            if ($missing !== []) {
                throw new RuntimeException(
                    'This case has no vehicle record yet, and the confirmed fields do not include '
                    . implode(', ', $missing) . '.'
                );
            }
        }

        DB::transaction(function () use ($vehicle, $attributes, $extraction, $staff): void {
            $vehicle->user_case_id = $extraction->user_case_id;
            $vehicle->fill($attributes);
            $vehicle->save();

            $extraction->update([
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ]);
        });

        CaseTimeline::log(
            $extraction->user_case_id,
            'extraction_confirmed',
            'Extracted data confirmed',
            count($attributes) . ' vehicle field(s) written from the reviewed '
                . str_replace('_', ' ', $extraction->document_type) . '.',
            [
                'extraction_id' => $extraction->id,
                'fields' => array_keys($attributes),
                'corrected' => $extraction->fields
                    ->where('status', DocumentExtractionField::STATUS_CORRECTED)
                    ->pluck('field_key')
                    ->values()
                    ->all(),
            ],
            $staff->id
        );

        return $vehicle->refresh();
    }

    public static function vehicleColumnFor(string $fieldKey): ?string
    {
        return self::COLUMN_MAP[$fieldKey] ?? null;
    }

    private function cast(string $fieldKey, string $value): int|string|Carbon
    {
        if (in_array($fieldKey, self::INTEGER_FIELDS, true)) {
            // Staff may type "128,450" or "12,500 lbs"; the column is an int.
            return (int) preg_replace('/[^0-9]/', '', $value);
        }

        if (in_array($fieldKey, self::DATE_FIELDS, true)) {
            try {
                return Carbon::parse($value)->startOfDay();
            } catch (InvalidFormatException $e) {
                throw new RuntimeException(
                    "'{$value}' is not a date that can be stored in {$fieldKey}.",
                    previous: $e
                );
            }
        }

        if (in_array($fieldKey, self::UPPERCASE_FIELDS, true)) {
            return strtoupper(trim($value));
        }

        return trim($value);
    }
}
