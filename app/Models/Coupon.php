<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'type', 'value', 'usage_limit', 'used_count', 'starts_at', 'ends_at', 'is_active'])]
class Coupon extends Model
{
}
