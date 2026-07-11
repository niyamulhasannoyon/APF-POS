<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'discount_percentage'])]
class CustomerGroup extends Model
{
    protected $casts = [
        'discount_percentage' => 'float'
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
