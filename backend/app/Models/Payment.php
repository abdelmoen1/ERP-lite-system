<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'debt_id',
        'amount',
        'notes',
        'method',
        'paid_at',
        'payment_group_id',
        'created_by',
        'is_reversed',
        'reversed_at',
        'reversal_reason',
    ];
    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}
