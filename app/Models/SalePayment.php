<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['order_id', 'method', 'amount', 'status', 'reference', 'notes'])]
class SalePayment extends Model
{
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
