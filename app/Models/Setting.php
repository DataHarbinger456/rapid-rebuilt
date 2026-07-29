<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public const TYPES = [
        'string' => 'String',
        'integer' => 'Integer',
        'decimal' => 'Decimal',
        'boolean' => 'Boolean',
        'json' => 'JSON',
    ];

    public const GROUPS = [
        'pricing' => 'Pricing',
        'schedule' => 'Schedule',
        'notifications' => 'Notifications',
        'company' => 'Company',
        'integrations' => 'Integrations',
        'general' => 'General',
    ];

    // =====================
    // ACCESSORS
    // =====================

    public function getTypedValueAttribute()
    {
        return self::castValue($this->value, $this->type);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // =====================
    // STATIC METHODS
    // =====================

    public static function get(string $key, $default = null)
    {
        $cacheKey = "setting.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    public static function set(string $key, $value, ?string $type = null): void
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            // Convert value based on type
            if ($type === 'json' || $setting->type === 'json') {
                $value = is_array($value) ? json_encode($value) : $value;
            } elseif ($type === 'boolean' || $setting->type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            $setting->update(['value' => $value]);
        } else {
            // Create new setting
            $type = $type ?? 'string';
            if ($type === 'json' && is_array($value)) {
                $value = json_encode($value);
            } elseif ($type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            self::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
            ]);
        }

        // Clear cache
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
    }

    public static function getByGroup(string $group): array
    {
        return self::byGroup($group)
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->typed_value])
            ->toArray();
    }

    public static function getAllCached(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            return self::all()
                ->mapWithKeys(fn($s) => [$s->key => $s->typed_value])
                ->toArray();
        });
    }

    public static function getPublicSettings(): array
    {
        return self::public()
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->typed_value])
            ->toArray();
    }

    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    // =====================
    // CONVENIENCE GETTERS
    // =====================

    public static function inspectionFee(): float
    {
        return (float) self::get('inspection_fee', 40.00);
    }

    public static function reinspectionFee(): float
    {
        return (float) self::get('reinspection_fee', 20.00);
    }

    public static function serviceFee(string $type = 'standard'): float
    {
        return (float) self::get("service_fee_{$type}", 150.00);
    }

    public static function businessHours(): array
    {
        return [
            'start' => self::get('business_hours_start', '08:00'),
            'end' => self::get('business_hours_end', '17:00'),
        ];
    }

    public static function workDays(): array
    {
        return self::get('work_days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
    }

    public static function appointmentDuration(): int
    {
        return (int) self::get('appointment_duration', 30);
    }

    public static function maxAppointmentsPerDay(): int
    {
        return (int) self::get('max_appointments_per_day', 16);
    }

    public static function companyName(): string
    {
        return self::get('company_name', 'Rapid Rebuilt');
    }

    public static function companyEmail(): string
    {
        return self::get('company_email', 'info@rapidrebuilt.com');
    }
}
