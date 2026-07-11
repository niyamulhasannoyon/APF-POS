<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'sku', 'barcode', 'price', 'cost', 'category_id', 'brand_id', 'tax_id', 'image_url', 'status'])]
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

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
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

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
