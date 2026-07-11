<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'description', 'logo_url', 'status'])]
class Brand extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
