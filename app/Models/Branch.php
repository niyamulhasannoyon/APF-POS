<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'address', 'phone', 'status'])]
class Branch extends Model
{
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'branch_product')
                    ->withPivot('stock_quantity')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
