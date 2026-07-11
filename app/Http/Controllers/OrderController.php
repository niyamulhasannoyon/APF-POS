<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['branch', 'user', 'customer']);

        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('offline_id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['branch', 'user', 'customer', 'items.product.category'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }
}
