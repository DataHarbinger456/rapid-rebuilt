<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'uploaded_file_id',
        'photo_type',
        'description',
        'latitude',
        'longitude',
        'taken_at',
        'device_info',
        'component_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'taken_at' => 'datetime',
    ];

    public const PHOTO_TYPES = [
        // VIN photos
        'vin_windshield' => 'VIN - Windshield',
        'vin_doorjamb' => 'VIN - Door Jamb',
        'vin_engine' => 'VIN - Engine',
        'vin_sticker' => 'VIN - Sticker',
        // Odometer
        'odometer' => 'Odometer',
        // Exterior photos
        'exterior_front' => 'Exterior - Front',
        'exterior_rear' => 'Exterior - Rear',
        'exterior_left' => 'Exterior - Left Side',
        'exterior_right' => 'Exterior - Right Side',
        'exterior_front_left' => 'Exterior - Front Left',
        'exterior_front_right' => 'Exterior - Front Right',
        'exterior_rear_left' => 'Exterior - Rear Left',
        'exterior_rear_right' => 'Exterior - Rear Right',
        // Interior photos
        'interior_dashboard' => 'Interior - Dashboard',
        'interior_front_seats' => 'Interior - Front Seats',
        'interior_rear_seats' => 'Interior - Rear Seats',
        'interior_trunk' => 'Interior - Trunk',
        // Component photos
        'component_engine' => 'Component - Engine',
        'component_frame' => 'Component - Frame',
        'component_transmission' => 'Component - Transmission',
        'component_other' => 'Component - Other',
        // Damage/Repair photos
        'damage_before' => 'Damage - Before',
        'repair_after' => 'Repair - After',
        // Other
        'other' => 'Other',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    public function uploadedFile()
    {
        return $this->belongsTo(UploadedFile::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getPhotoTypeLabelAttribute(): string
    {
        return self::PHOTO_TYPES[$this->photo_type] ?? $this->photo_type;
    }

    public function getHasLocationAttribute(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->uploadedFile?->url;
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeByType($query, $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeVinPhotos($query)
    {
        return $query->where('photo_type', 'like', 'vin_%');
    }

    public function scopeExteriorPhotos($query)
    {
        return $query->where('photo_type', 'like', 'exterior_%');
    }

    public function scopeInteriorPhotos($query)
    {
        return $query->where('photo_type', 'like', 'interior_%');
    }

    public function scopeComponentPhotos($query)
    {
        return $query->where('photo_type', 'like', 'component_%');
    }

    // =====================
    // METHODS
    // =====================

    public static function getRequiredTypes(): array
    {
        return [
            'vin_windshield',
            'vin_doorjamb',
            'odometer',
            'exterior_front',
            'exterior_rear',
            'exterior_left',
            'exterior_right',
        ];
    }

    public static function getPhotoTypeOptions(): array
    {
        return self::PHOTO_TYPES;
    }
}
