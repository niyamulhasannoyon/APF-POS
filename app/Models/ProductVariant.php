<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'sku', 'barcode', 'option_values', 'cost', 'price'])]
class ProductVariant extends Model
{
    protected $casts = [
        'option_values' => 'array',
        'cost' => 'float',
        'price' => 'float'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_variant')
                    ->withPivot('stock_quantity')
                    ->withTimestamps();
    }
}
