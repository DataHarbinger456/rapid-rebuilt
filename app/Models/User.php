<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'user_type',
        'address_1',
        'address_2',
        'city',
        'states',
        'zip',
        'phone',
        'county',
        'dealer_name',
        'license_type',
        'license_number',
        'sales_tax_registration_number',
        'region',
        'zones',
        'verification_token',
        'email_verified_at',
        // Inspector fields
        'inspector_license',
        'inspector_certification_date',
        'inspector_active',
        // Profile
        'avatar',
        'preferences',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'inspector_certification_date' => 'date',
        'inspector_active' => 'boolean',
        'preferences' => 'array',
        'last_login_at' => 'datetime',
    ];

    public const USER_TYPES = [
        'user' => 'Customer',
        'dealer' => 'Dealer',
        'inspector' => 'Inspector',
        'staff' => 'Staff',
        'admin' => 'Administrator',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function cases()
    {
        return $this->hasMany(UserCase::class);
    }

    public function assignedInspections()
    {
        return $this->hasMany(UserCase::class, 'assigned_inspector_id');
    }

    public function conductedInspections()
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function timelineEvents()
    {
        return $this->hasMany(CaseTimeline::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Alias for full_name - compatibility with views/templates
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Alias for user_type - compatibility with policies/gates
     */
    public function getRoleAttribute(): string
    {
        return $this->user_type;
    }

    public function getUserTypeLabelAttribute(): string
    {
        return self::USER_TYPES[$this->user_type] ?? $this->user_type;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_1,
            $this->address_2,
            $this->city,
            $this->states,
            $this->zip,
        ]);

        return implode(', ', $parts);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }

        return null;
    }

    public function getInitialsAttribute(): string
    {
        $first = $this->first_name ? strtoupper(substr($this->first_name, 0, 1)) : '';
        $last = $this->last_name ? strtoupper(substr($this->last_name, 0, 1)) : '';

        return $first . $last;
    }

    // =====================
    // ROLE CHECKS
    // =====================

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isStaff(): bool
    {
        return in_array($this->user_type, ['admin', 'staff']);
    }

    public function isInspector(): bool
    {
        return $this->user_type === 'inspector';
    }

    public function isActiveInspector(): bool
    {
        return $this->isInspector() && $this->inspector_active;
    }

    public function isDealer(): bool
    {
        return $this->user_type === 'dealer';
    }

    public function isCustomer(): bool
    {
        return in_array($this->user_type, ['user', 'dealer']);
    }

    public function canManageCases(): bool
    {
        return in_array($this->user_type, ['admin', 'staff', 'inspector']);
    }

    public function canConductInspections(): bool
    {
        return $this->isActiveInspector();
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeAdmins($query)
    {
        return $query->where('user_type', 'admin');
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('user_type', ['admin', 'staff']);
    }

    public function scopeInspectors($query)
    {
        return $query->where('user_type', 'inspector');
    }

    public function scopeActiveInspectors($query)
    {
        return $query->where('user_type', 'inspector')
                     ->where('inspector_active', true);
    }

    public function scopeDealers($query)
    {
        return $query->where('user_type', 'dealer');
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('user_type', ['user', 'dealer']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    // =====================
    // INSPECTOR METHODS
    // =====================

    public function activateAsInspector(): void
    {
        $this->update(['inspector_active' => true]);
    }

    public function deactivateAsInspector(): void
    {
        $this->update(['inspector_active' => false]);
    }

    public function getAssignedCasesCount(): int
    {
        return $this->assignedInspections()->active()->count();
    }

    public function getTodaysAppointments()
    {
        return $this->assignedInspections()
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getInspectionStats(string $period = 'month'): array
    {
        $query = $this->conductedInspections();

        if ($period === 'month') {
            $query->whereMonth('completed_at', now()->month)
                  ->whereYear('completed_at', now()->year);
        } elseif ($period === 'week') {
            $query->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        return [
            'total' => $query->count(),
            'passed' => $query->clone()->where('result', 'passed')->count(),
            'failed' => $query->clone()->where('result', 'failed')->count(),
        ];
    }

    // =====================
    // PREFERENCES
    // =====================

    public function getPreference(string $key, $default = null)
    {
        $preferences = $this->preferences ?? [];

        return $preferences[$key] ?? $default;
    }

    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;

        $this->update(['preferences' => $preferences]);
    }

    // =====================
    // AUTH TRACKING
    // =====================

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
