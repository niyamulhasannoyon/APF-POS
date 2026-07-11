<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'phone', 'email', 'loyalty_points', 'customer_group_id', 'store_credit', 'outstanding_dues'])]
class Customer extends Model
{
    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
            'store_credit' => 'float',
            'outstanding_dues' => 'float',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }
}
