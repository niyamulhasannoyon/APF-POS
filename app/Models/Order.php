<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'offline_id',
    'branch_id',
    'user_id',
    'customer_id',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total_amount',
    'payment_method',
    'payment_status',
    'status',
    'notes',
    'synced_at',
    'created_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
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

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
