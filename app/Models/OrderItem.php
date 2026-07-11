<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['order_id', 'product_id', 'quantity', 'price', 'cost', 'discount', 'subtotal'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
