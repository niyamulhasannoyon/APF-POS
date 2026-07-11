<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'slug'])]
class Category extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
