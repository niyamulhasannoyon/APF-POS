<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'branch_id', 'batch_number', 'expiry_date', 'stock_quantity'])]
class ProductBatch extends Model
{
    protected $casts = [
        'expiry_date' => 'date',
        'stock_quantity' => 'float'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
