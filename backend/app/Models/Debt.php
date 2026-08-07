<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'amount',
        'remaining_amount',
        'status',
    ];

    // Relationship between Customer and Debts
    public function customer()
    {
        // A Debt belong to one Customer
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
