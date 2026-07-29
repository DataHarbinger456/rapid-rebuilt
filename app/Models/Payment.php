<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable; // Optional

class Payment extends Model
{
    protected $fillable = [
        'user_id', 
        'amount', 
        'status', 
        'stripe_payment_intent_id',
        'payment_method'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Update User model
class User extends Authenticatable
{
    use Billable; // If using Laravel Cashier

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}