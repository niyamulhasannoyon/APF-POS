<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['branch_id', 'expense_category_id', 'user_id', 'amount', 'date', 'reference', 'notes'])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
