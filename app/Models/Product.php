<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'sku', 'barcode', 'price', 'cost', 'category_id', 'image_url', 'status'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_product')
                    ->withPivot('stock_quantity')
                    ->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
