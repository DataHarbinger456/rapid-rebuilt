<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_case_id',
        'part_type',
        'other_description',
        'condition',
        'source_name',
        'source_phone',
        'source_address',
        'source_city',
        'source_state',
        'source_zip',
        'part_number',
        'serial_number',
        'receipt_file_id',
        'purchase_price',
        'purchase_date',
        'receipt_number',
        'verified',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Lista de componentes mayores segun Florida Statute 319.30
    public const PART_TYPES = [
        'fender_front_left' => 'Front Left Fender',
        'fender_front_right' => 'Front Right Fender',
        'fender_rear_left' => 'Rear Left Fender',
        'fender_rear_right' => 'Rear Right Fender',
        'hood' => 'Hood',
        'bumper_front' => 'Front Bumper',
        'bumper_rear' => 'Rear Bumper',
        'cowl_assembly' => 'Cowl Assembly',
        'quarter_panel_left' => 'Left Quarter Panel',
        'quarter_panel_right' => 'Right Quarter Panel',
        'trunk_lid' => 'Trunk Lid',
        'deck_lid' => 'Deck Lid',
        'door_front_left' => 'Front Left Door',
        'door_front_right' => 'Front Right Door',
        'door_rear_left' => 'Rear Left Door',
        'door_rear_right' => 'Rear Right Door',
        'floor_pan' => 'Floor Pan',
        'engine' => 'Engine',
        'frame' => 'Frame',
        'transmission' => 'Transmission',
        'catalytic_converter' => 'Catalytic Converter',
        'airbag_driver' => 'Driver Airbag',
        'airbag_passenger' => 'Passenger Airbag',
        'airbag_side_left' => 'Left Side Airbag',
        'airbag_side_right' => 'Right Side Airbag',
        'airbag_curtain' => 'Curtain Airbag',
        // Electric/Hybrid components
        'electric_traction_motor' => 'Electric Traction Motor',
        'electronic_transmission' => 'Electronic Transmission',
        'charge_port' => 'Charge Port',
        'dc_power_converter' => 'DC Power Converter',
        'onboard_charger' => 'Onboard Charger',
        'power_electronics_controller' => 'Power Electronics Controller',
        'thermal_system' => 'Thermal System',
        'traction_battery' => 'Traction Battery',
        'other' => 'Other',
    ];

    public const CONDITIONS = [
        'new' => 'New',
        'used' => 'Used',
        'repaired' => 'Repaired',
        'rebuilt' => 'Rebuilt',
        'original' => 'Original',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function receiptFile()
    {
        return $this->belongsTo(UploadedFile::class, 'receipt_file_id');
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function inspectionPhotos()
    {
        return $this->hasMany(InspectionPhoto::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getPartTypeLabelAttribute(): string
    {
        return self::PART_TYPES[$this->part_type] ?? $this->part_type;
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function getFullSourceAddressAttribute(): string
    {
        $parts = array_filter([
            $this->source_address,
            $this->source_city,
            $this->source_state,
            $this->source_zip,
        ]);
        return implode(', ', $parts);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('part_type', $type);
    }

    public function scopeWithReceipt($query)
    {
        return $query->whereNotNull('receipt_file_id');
    }

    public function scopeWithoutReceipt($query)
    {
        return $query->whereNull('receipt_file_id');
    }

    // =====================
    // METHODS
    // =====================

    public function verify(User $inspector, string $notes = null): void
    {
        $this->update([
            'verified' => true,
            'verified_by' => $inspector->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    public function hasReceipt(): bool
    {
        return $this->receipt_file_id !== null;
    }

    public static function getPartTypeOptions(): array
    {
        return self::PART_TYPES;
    }

    public static function getConditionOptions(): array
    {
        return self::CONDITIONS;
    }
}
