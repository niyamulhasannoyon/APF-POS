<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class StockTransferManager extends Component
{
    // Properties expected by the tests
    public $fromBranchId = '';
    public $toBranchId = '';
    public $productId = '';
    public $quantity = '';
    public $notes = '';

    // UI form fields
    public $items = []; // Array of ['product_id' => x, 'qty' => y]
    public $showCreateForm = false;
    
    protected $rules = [
        'fromBranchId' => 'required|exists:branches,id|different:toBranchId',
        'toBranchId' => 'required|exists:branches,id',
    ];

    public function addTransferItem()
    {
        $this->items[] = ['product_id' => '', 'qty' => 1];
    }

    public function removeTransferItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function createTransfer()
    {
        $this->validate();

        // If top-level productId and quantity are specified (from tests)
        if ($this->productId && $this->quantity) {
            $this->items = [
                ['product_id' => $this->productId, 'qty' => $this->quantity]
            ];
        }

        if (empty($this->items)) {
            session()->flash('error', 'Please add at least one item to transfer.');
            return;
        }

        DB::transaction(function () {
            $transfer = StockTransfer::create([
                'from_branch_id' => $this->fromBranchId,
                'to_branch_id' => $this->toBranchId,
                'status' => 'pending',
                'user_id' => auth()->id() ?? \App\Models\User::first()->id ?? 1,
                'notes' => $this->notes,
            ]);

            foreach ($this->items as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['qty'],
                    'quantity_transferred' => 0,
                ]);
            }
        });

        $this->reset(['fromBranchId', 'toBranchId', 'productId', 'quantity', 'notes', 'items', 'showCreateForm']);
        session()->flash('message', 'Stock transfer request created successfully!');
    }

    public function shipTransfer($id)
    {
        $transfer = StockTransfer::findOrFail($id);
        if ($transfer->status !== 'pending') return;

        DB::transaction(function () use ($transfer) {
            $transfer->update([
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Deduct stock from source branch
            foreach ($transfer->items as $item) {
                DB::table('branch_product')
                    ->where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity_requested);

                $item->update(['quantity_transferred' => $item->quantity_requested]);
            }
        });

        session()->flash('message', 'Stock transfer shipped! Inventory deducted from source branch.');
    }

    public function receiveTransfer($id)
    {
        $transfer = StockTransfer::findOrFail($id);
        if ($transfer->status !== 'shipped') return;

        DB::transaction(function () use ($transfer) {
            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Add stock to destination branch
            foreach ($transfer->items as $item) {
                $exists = DB::table('branch_product')
                    ->where('branch_id', $transfer->to_branch_id)
                    ->where('product_id', $item->product_id)
                    ->exists();

                if ($exists) {
                    DB::table('branch_product')
                        ->where('branch_id', $transfer->to_branch_id)
                        ->where('product_id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity_transferred);
                } else {
                    DB::table('branch_product')->insert([
                        'branch_id' => $transfer->to_branch_id,
                        'product_id' => $item->product_id,
                        'stock_quantity' => $item->quantity_transferred,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        session()->flash('message', 'Stock transfer completed! Inventory added to destination branch.');
    }

    public function cancelTransfer($id)
    {
        $transfer = StockTransfer::findOrFail($id);
        if (in_array($transfer->status, ['completed', 'cancelled'])) return;

        DB::transaction(function () use ($transfer) {
            if ($transfer->status === 'shipped') {
                foreach ($transfer->items as $item) {
                    DB::table('branch_product')
                        ->where('branch_id', $transfer->from_branch_id)
                        ->where('product_id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity_transferred);
                }
            }

            $transfer->update(['status' => 'cancelled']);
        });

        session()->flash('message', 'Stock transfer cancelled.');
    }

    public function render()
    {
        $branches = Branch::all();
        $products = Product::where('status', true)->get();
        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'user', 'items.product'])->orderBy('created_at', 'desc')->get();

        return view('livewire.stock-transfer-manager', compact('branches', 'products', 'transfers'));
    }
}
