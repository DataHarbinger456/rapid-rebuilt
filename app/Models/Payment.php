<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_number',
        'user_id',
        'user_case_id',
        'amount',
        'status',
        'transaction_id',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    /**
     * Generate the next sequential invoice number (INV000000101 style).
     */
    public static function nextInvoiceNumber(): string
    {
        $lastId = (int) static::max('id');

        return 'INV' . str_pad((string) ($lastId + 101), 9, '0', STR_PAD_LEFT);
    }
}
