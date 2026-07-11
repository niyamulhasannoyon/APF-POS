<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'name', 'symbol', 'decimal_places', 'is_default', 'is_active'])]
class Currency extends Model
{
}
