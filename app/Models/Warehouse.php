<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'address', 'phone', 'branch_id', 'status'])]
class Warehouse extends Model
{
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
