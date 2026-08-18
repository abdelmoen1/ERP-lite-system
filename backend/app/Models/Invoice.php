<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'total_amount',
        'has_debt',
        'payment_method',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }
}
