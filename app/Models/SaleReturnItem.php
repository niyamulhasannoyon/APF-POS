<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['sale_return_id', 'order_item_id', 'product_id', 'quantity', 'price'])]
class SaleReturnItem extends Model
{
    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
