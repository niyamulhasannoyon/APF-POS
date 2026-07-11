<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['purchase_order_id', 'product_id', 'product_variant_id', 'quantity_ordered', 'quantity_received', 'unit_cost', 'subtotal'])]
class PurchaseOrderItem extends Model
{
    protected $casts = [
        'quantity_ordered' => 'float',
        'quantity_received' => 'float',
        'unit_cost' => 'float',
        'subtotal' => 'float'
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
