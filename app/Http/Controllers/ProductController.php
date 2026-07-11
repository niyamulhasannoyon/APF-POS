<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'branches']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(15);
        $branches = Branch::all();

        return view('admin.products.index', compact('products', 'branches'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:100',
            'barcode' => 'nullable|string|unique:products,barcode|max:100',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'price' => $request->price,
            'cost' => $request->cost,
            'category_id' => $request->category_id,
            'status' => $request->has('status') ? $request->status : true,
        ]);

        // Automatically initialize stock = 0 for all branches
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $branch->products()->attach($product->id, ['stock_quantity' => 0]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|numeric',
        ]);

        DB::table('branch_product')
            ->updateOrInsert(
                ['branch_id' => $request->branch_id, 'product_id' => $request->product_id],
                ['stock_quantity' => $request->quantity, 'updated_at' => now()]
            );

        return redirect()->route('admin.products.index')->with('success', 'Stock level adjusted successfully.');
    }
}
