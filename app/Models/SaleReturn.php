<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'offline_id',
    'branch_id',
    'user_id',
    'customer_id',
    'order_id',
    'refund_amount',
    'refund_method',
    'status',
    'notes',
    'synced_at',
])]
class SaleReturn extends Model
{
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
