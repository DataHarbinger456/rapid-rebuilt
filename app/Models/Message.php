<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_case_id',
        'sender_id',
        'recipient_id',
        'message',
        'attachments',
        'is_internal',
        'is_read',
        'read_at',
        'message_type',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public const MESSAGE_TYPES = [
        'text' => 'Text Message',
        'document_request' => 'Document Request',
        'status_update' => 'Status Update',
        'system_notification' => 'System Notification',
        'reminder' => 'Reminder',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getMessageTypeLabelAttribute(): string
    {
        return self::MESSAGE_TYPES[$this->message_type] ?? $this->message_type;
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }

    public function getAttachmentCountAttribute(): int
    {
        return is_array($this->attachments) ? count($this->attachments) : 0;
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    // =====================
    // METHODS
    // =====================

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function isFromStaff(): bool
    {
        return $this->sender && in_array($this->sender->user_type, ['admin', 'staff', 'inspector']);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender && in_array($this->sender->user_type, ['user', 'dealer']);
    }

    // =====================
    // STATIC METHODS
    // =====================

    public static function sendSystemMessage(
        int $userCaseId,
        int $recipientId,
        string $message,
        string $type = 'system_notification'
    ): self {
        return self::create([
            'user_case_id' => $userCaseId,
            'sender_id' => null,
            'recipient_id' => $recipientId,
            'message' => $message,
            'message_type' => $type,
            'is_internal' => false,
        ]);
    }

    public static function requestDocument(
        int $userCaseId,
        int $recipientId,
        string $documentDescription
    ): self {
        return self::create([
            'user_case_id' => $userCaseId,
            'sender_id' => auth()->id(),
            'recipient_id' => $recipientId,
            'message' => "Document Request: {$documentDescription}",
            'message_type' => 'document_request',
            'is_internal' => false,
        ]);
    }
}
