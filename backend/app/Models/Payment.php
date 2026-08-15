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
        'created_by',
    ];
    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}
