<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Store extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
    ];
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
