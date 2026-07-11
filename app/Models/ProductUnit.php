<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'name', 'conversion_rate', 'sku', 'barcode', 'cost', 'price'])]
class ProductUnit extends Model
{
    protected $casts = [
        'conversion_rate' => 'float',
        'cost' => 'float',
        'price' => 'float'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
