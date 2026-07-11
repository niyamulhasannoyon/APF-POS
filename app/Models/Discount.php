<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'type', 'value', 'starts_at', 'ends_at', 'is_active'])]
class Discount extends Model
{
}
