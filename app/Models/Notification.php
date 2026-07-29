<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification',
        'user_id',
        'user_case_id',
        'type',
        'title',
        'status',
        'action_url',
        'read_at',
        'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    public const TYPES = [
        'info' => 'Information',
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
        'reminder' => 'Reminder',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'read' => 'Read',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getIconAttribute(): string
    {
        $icons = [
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'times-circle',
            'reminder' => 'bell',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    public function getColorAttribute(): string
    {
        $colors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'reminder' => 'purple',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =====================
    // METHODS
    // =====================

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
        }
    }

    public static function markAllAsReadForUser(int $userId): int
    {
        return self::forUser($userId)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
    }

    // =====================
    // STATIC CREATION METHODS
    // =====================

    public static function notify(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?int $userCaseId = null,
        ?string $actionUrl = null,
        ?array $data = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'user_case_id' => $userCaseId,
            'title' => $title,
            'notification' => $message,
            'type' => $type,
            'status' => 'sent',
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    public static function info(int $userId, string $title, string $message, ?int $caseId = null): self
    {
        return self::notify($userId, $title, $message, 'info', $caseId);
    }

    public static function success(int $userId, string $title, string $message, ?int $caseId = null): self
    {
        return self::notify($userId, $title, $message, 'success', $caseId);
    }

    public static function warning(int $userId, string $title, string $message, ?int $caseId = null): self
    {
        return self::notify($userId, $title, $message, 'warning', $caseId);
    }

    public static function error(int $userId, string $title, string $message, ?int $caseId = null): self
    {
        return self::notify($userId, $title, $message, 'error', $caseId);
    }

    public static function reminder(int $userId, string $title, string $message, ?int $caseId = null): self
    {
        return self::notify($userId, $title, $message, 'reminder', $caseId);
    }
}
