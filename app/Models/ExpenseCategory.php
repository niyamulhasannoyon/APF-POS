<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'description', 'status'])]
class ExpenseCategory extends Model
{
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
