<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function debts()
    {
        return $this->hasManyThrough(
            Debt::class,
            Invoice::class,
            'customer_id', // Foreign key on invoices
            'invoice_id',  // Foreign key on debts
            'id',          // Local key on customers
            'id'           // Local key on invoices
        );
    }
}
