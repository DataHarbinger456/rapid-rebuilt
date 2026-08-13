<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    use HasFactory;

    /**
     * The only two answers the HSMV forms accept for how many digits the
     * odometer physically displays. Each form has one box per value.
     */
    public const ODOMETER_DIGIT_COUNTS = [5, 6];

    /**
     * inspections.result => [staff-facing label, bootstrap colour].
     *
     * Lives here rather than in a view so every screen describes an
     * inspection the same way.
     */
    public const RESULTS = [
        'pending' => ['Not started', 'secondary'],
        'in_progress' => ['In progress', 'info'],
        'passed' => ['Passed', 'success'],
        'failed' => ['Failed', 'danger'],
        'incomplete' => ['Incomplete', 'warning'],
    ];

    protected $fillable = [
        'user_case_id',
        'scheduling_id',
        'inspector_id',
        'result',
        'vin_verified',
        'vin_location_1',
        'vin_location_2',
        'vin_plates_match',
        'vin_no_alterations',
        'odometer_reading_verified',
        'odometer_functional',
        'odometer_digit_count',
        'components_verified',
        'all_receipts_match',
        'brakes_functional',
        'lights_functional',
        'steering_functional',
        'horn_functional',
        'wipers_functional',
        'airbags_present',
        'seatbelts_functional',
        'inspector_notes',
        'failure_reasons',
        'inspector_signature',
        'signed_at',
        'audit_number',
        'region_number',
        'decal_number',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'vin_verified' => 'boolean',
        'vin_plates_match' => 'boolean',
        'vin_no_alterations' => 'boolean',
        'odometer_reading_verified' => 'integer',
        'odometer_functional' => 'boolean',
        'odometer_digit_count' => 'integer',
        'components_verified' => 'array',
        'all_receipts_match' => 'boolean',
        'brakes_functional' => 'boolean',
        'lights_functional' => 'boolean',
        'steering_functional' => 'boolean',
        'horn_functional' => 'boolean',
        'wipers_functional' => 'boolean',
        'airbags_present' => 'boolean',
        'seatbelts_functional' => 'boolean',
        'signed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // =====================
    // ACCESSORS
    // =====================

    public function getResultLabelAttribute(): string
    {
        return self::RESULTS[$this->result][0] ?? $this->result;
    }

    public function getResultColourAttribute(): string
    {
        return self::RESULTS[$this->result][1] ?? 'secondary';
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function scheduling()
    {
        return $this->belongsTo(UserFormScheduling::class, 'scheduling_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function photos()
    {
        return $this->hasMany(InspectionPhoto::class);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopePending($query)
    {
        return $query->where('result', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('result', 'in_progress');
    }

    public function scopePassed($query)
    {
        return $query->where('result', 'passed');
    }

    public function scopeFailed($query)
    {
        return $query->where('result', 'failed');
    }

    public function scopeByInspector($query, $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getIsPendingAttribute(): bool
    {
        return $this->result === 'pending';
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->result === 'passed';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->result === 'failed';
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->result, ['passed', 'failed']);
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            // Carbon 3 returns a signed float; duration is start->end so it is
            // positive — truncate to whole minutes for the int contract.
            return (int) $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }

    // =====================
    // METHODS
    // =====================

    public function start(): void
    {
        $this->update([
            'result' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function pass(string $notes = null): void
    {
        $this->update([
            'result' => 'passed',
            'inspector_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    public function fail(string $reasons): void
    {
        $this->update([
            'result' => 'failed',
            'failure_reasons' => $reasons,
            'completed_at' => now(),
        ]);
    }

    public function sign(string $signatureBase64): void
    {
        $this->update([
            'inspector_signature' => $signatureBase64,
            'signed_at' => now(),
        ]);
    }

    public function allSafetyChecksPassed(): bool
    {
        return $this->brakes_functional
            && $this->lights_functional
            && $this->steering_functional
            && $this->seatbelts_functional;
    }

    public function allVinChecksPassed(): bool
    {
        return $this->vin_verified
            && $this->vin_plates_match
            && $this->vin_no_alterations;
    }

    public static function getChecklistTemplate(): array
    {
        return [
            'vin_verification' => [
                'title' => 'VIN Verification',
                'items' => [
                    'vin_location_1' => ['label' => 'VIN under windshield', 'type' => 'text', 'required' => true],
                    'vin_location_2' => ['label' => 'VIN on door jamb', 'type' => 'text', 'required' => true],
                    'vin_plates_match' => ['label' => 'All VIN locations match', 'type' => 'boolean', 'required' => true],
                    'vin_no_alterations' => ['label' => 'No signs of alterations', 'type' => 'boolean', 'required' => true],
                ]
            ],
            'odometer' => [
                'title' => 'Odometer Verification',
                'items' => [
                    'odometer_reading_verified' => ['label' => 'Current reading', 'type' => 'number', 'required' => true],
                    'odometer_functional' => ['label' => 'Odometer functional', 'type' => 'boolean', 'required' => true],
                    // Three HSMV forms ask how many digits the cluster shows,
                    // and only someone looking at it can answer. Required: the
                    // inspector is already reading the odometer for the box
                    // above, and a guess here goes onto a sworn declaration.
                    'odometer_digit_count' => [
                        'label' => 'Digits shown on the odometer',
                        'type' => 'choice',
                        'options' => self::ODOMETER_DIGIT_COUNTS,
                        'required' => true,
                    ],
                ]
            ],
            'safety_systems' => [
                'title' => 'Safety Systems',
                'items' => [
                    'brakes_functional' => ['label' => 'Brakes functional', 'type' => 'boolean', 'required' => true],
                    'lights_functional' => ['label' => 'All lights functional', 'type' => 'boolean', 'required' => true],
                    'steering_functional' => ['label' => 'Steering functional', 'type' => 'boolean', 'required' => true],
                    'horn_functional' => ['label' => 'Horn functional', 'type' => 'boolean', 'required' => true],
                    'wipers_functional' => ['label' => 'Wipers functional', 'type' => 'boolean', 'required' => true],
                    'seatbelts_functional' => ['label' => 'Seatbelts functional', 'type' => 'boolean', 'required' => true],
                    'airbags_present' => ['label' => 'Airbags present (if applicable)', 'type' => 'boolean', 'required' => false],
                ]
            ],
        ];
    }
}
