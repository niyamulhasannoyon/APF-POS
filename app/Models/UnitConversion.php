<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['from_unit_id', 'to_unit_id', 'factor', 'is_base'])]
class UnitConversion extends Model
{
    public function fromUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'to_unit_id');
    }
}
