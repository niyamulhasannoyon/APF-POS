<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['branch_id', 'user_id', 'customer_id', 'items', 'subtotal', 'tax_amount', 'discount_amount'])]
class HeldSale extends Model
{
    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
