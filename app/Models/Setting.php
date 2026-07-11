<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['group', 'key', 'value', 'branch_id'])]
class Setting extends Model
{
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
