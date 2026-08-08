<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * How the vehicle was built, matching the vehicles.vehicle_type enum.
     *
     * These drive the TYPE checkboxes in Section 3 of the HSMV 82040; see
     * config/hsmv_forms.php. 'mobile_home' and 'other' have no box of their
     * own on that form.
     */
    public const VEHICLE_TYPES = [
        'rebuilt' => 'Rebuilt',
        'aspt' => 'Assembled from Parts (ASPT)',
        'kit_car' => 'Kit Car',
        'custom_vehicle' => 'Custom Vehicle',
        'street_rod' => 'Street Rod',
        'replica' => 'Replica',
        'mobile_home' => 'Mobile Home',
        'other' => 'Other',
    ];

    /**
     * Issuing states for a title, matching the two-character
     * vehicles.title_state column.
     *
     * Florida is listed first because it is the common case; the rest follow
     * alphabetically. On the HSMV 82040 a Florida title is an Original
     * application and anything else is a Transfer, so this list has to cover
     * every state the business actually sees rather than a Florida/other
     * shorthand.
     */
    public const US_STATES = [
        'FL' => 'Florida',
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];

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
