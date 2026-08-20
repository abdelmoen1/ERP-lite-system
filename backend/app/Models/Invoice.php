<?php

namespace App\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use App\Enums\InvoiceSource;

class Invoice extends Model
{
    use BelongsToStore;
    protected $fillable = [
        'store_id',
        'customer_id',
        'total_amount',
        'has_debt',
        'payment_method',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'has_debt' => 'boolean',
            'source' => InvoiceSource::class,
        ];
    }


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
