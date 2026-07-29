<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_case_id',
        'vin',
        'title_number',
        'title_state',
        'title_brand',
        'year',
        'make',
        'model',
        'body_type',
        'color',
        'weight',
        'engine_type',
        'fuel_type',
        'license_plate',
        'previous_state',
        'odometer_reading',
        'odometer_date',
        'odometer_status',
        'vehicle_type',
        'vin_decoded_data',
    ];

    protected $casts = [
        'year' => 'integer',
        'weight' => 'integer',
        'odometer_reading' => 'integer',
        'odometer_date' => 'date',
        'vin_decoded_data' => 'array',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'user_case_id', 'user_case_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getFullNameAttribute()
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    public function getFormattedVinAttribute()
    {
        return strtoupper($this->vin);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeByVin($query, $vin)
    {
        return $query->where('vin', strtoupper($vin));
    }

    public function scopeSalvage($query)
    {
        return $query->where('title_brand', 'salvage');
    }

    public function scopeRebuilt($query)
    {
        return $query->where('title_brand', 'rebuilt');
    }

    // =====================
    // METHODS
    // =====================

    public function isValidVin(): bool
    {
        return strlen($this->vin) === 17 && preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $this->vin);
    }
}
