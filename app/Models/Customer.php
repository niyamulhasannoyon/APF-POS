<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'phone', 'email', 'loyalty_points'])]
class Customer extends Model
{
    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
