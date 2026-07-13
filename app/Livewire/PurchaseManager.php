<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PurchaseManager extends Component
{
    // Properties expected by the tests
    public $supplierId = '';
    public $branchId = '';
    public $selectedProductId = '';
    public $qtyOrdered = 1;
    public $unitCost = 0.00;
    public $paidAmount = 0.00;
    public $notes = '';

    // Draft items & receive items state
    public $draftItems = [];
    public $receiveItems = [];

    public $showCreateForm = false;
    public $showReceiveModal = false;
    public $selectedPoForReceive = null;

    protected $rules = [
        'supplierId' => 'required|exists:suppliers,id',
        'branchId' => 'required|exists:branches,id',
    ];

    public function mount()
    {
        $this->draftItems = [];
    }

    public function addToDraft()
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'qtyOrdered' => 'required|numeric|min:0.01',
            'unitCost' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($this->selectedProductId);

        $this->draftItems[] = [
            'product_id' => $this->selectedProductId,
            'name' => $product->name,
            'sku' => $product->sku,
            'qty' => $this->qtyOrdered,
            'cost' => $this->unitCost,
        ];

        // Reset item fields
        $this->selectedProductId = '';
        $this->qtyOrdered = 1;
        $this->unitCost = 0.00;
    }

    public function removeFromDraft($index)
    {
        unset($this->draftItems[$index]);
        $this->draftItems = array_values($this->draftItems);
    }

    public function createPurchaseOrder()
    {
        $this->savePurchaseOrder();
    }

    public function savePurchaseOrder()
    {
        $this->validate();

        if (empty($this->draftItems)) {
            session()->flash('error', 'Please add at least one item to draft.');
            return;
        }

        DB::transaction(function () {
            $totalAmount = 0;
            foreach ($this->draftItems as $item) {
                $totalAmount += $item['qty'] * $item['cost'];
            }

            $unpaidAmount = $totalAmount - $this->paidAmount;

            $po = PurchaseOrder::create([
                'offline_id' => 'po-' . self::crypto_random_uuid(),
                'supplier_id' => $this->supplierId,
                'branch_id' => $this->branchId,
                'status' => 'ordered',
                'total_amount' => $totalAmount,
                'paid_amount' => $this->paidAmount,
                'payment_status' => $this->paidAmount >= $totalAmount ? 'paid' : ($this->paidAmount > 0 ? 'partially_paid' : 'unpaid'),
                'notes' => $this->notes,
                'ordered_at' => now(),
            ]);

            foreach ($this->draftItems as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['qty'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['cost'],
                    'subtotal' => $item['qty'] * $item['cost'],
                ]);
            }

            // Dues incremented by unpaid amount ($totalAmount - paidAmount)
            $supplier = Supplier::find($this->supplierId);
            if ($supplier && $unpaidAmount > 0) {
                $supplier->increment('outstanding_balance', $unpaidAmount);
            }
        });

        $this->reset(['supplierId', 'branchId', 'notes', 'paidAmount', 'draftItems', 'showCreateForm']);
        session()->flash('message', 'Purchase Order created successfully!');
    }

    public function openReceiveModal($poId)
    {
        $this->selectedPoForReceive = PurchaseOrder::with('items.product')->findOrFail($poId);
        $this->receiveItems = [];
        foreach ($this->selectedPoForReceive->items as $index => $item) {
            $this->receiveItems[$index] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'qty_ordered' => $item->quantity_ordered,
                'qty_received' => $item->quantity_ordered, // default to ordered qty
            ];
        }
        $this->showReceiveModal = true;
    }
    public function completePurchaseOrder($id)
    {
        $this->openReceiveModal($id);
        $this->submitReceive();
    }
    public function submitReceive()
    {
        if (!$this->selectedPoForReceive) return;

        DB::transaction(function () {
            $po = PurchaseOrder::findOrFail($this->selectedPoForReceive->id);
            $po->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            foreach ($this->receiveItems as $rItem) {
                $poItem = PurchaseOrderItem::findOrFail($rItem['id']);
                $poItem->update([
                    'quantity_received' => $rItem['qty_received']
                ]);

                // Increment branch stock
                $exists = DB::table('branch_product')
                    ->where('branch_id', $po->branch_id)
                    ->where('product_id', $rItem['product_id'])
                    ->exists();

                if ($exists) {
                    DB::table('branch_product')
                        ->where('branch_id', $po->branch_id)
                        ->where('product_id', $rItem['product_id'])
                        ->increment('stock_quantity', $rItem['qty_received']);
                } else {
                    DB::table('branch_product')->insert([
                        'branch_id' => $po->branch_id,
                        'product_id' => $rItem['product_id'],
                        'stock_quantity' => $rItem['qty_received'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->showReceiveModal = false;
        $this->selectedPoForReceive = null;
        $this->receiveItems = [];
        session()->flash('message', 'Purchase order completed and stock updated.');
    }

    public function cancelPurchaseOrder($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        if (in_array($po->status, ['completed', 'cancelled'])) return;

        DB::transaction(function () use ($po) {
            $po->update(['status' => 'cancelled']);

            // Deduct unpaid amount from supplier outstanding balance
            $unpaidAmount = $po->total_amount - $po->paid_amount;
            if ($unpaidAmount > 0) {
                $po->supplier->decrement('outstanding_balance', $unpaidAmount);
            }
        });

        session()->flash('message', 'Purchase order cancelled.');
    }

    private static function crypto_random_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function render()
    {
        $suppliers = Supplier::where('status', true)->get();
        $branches = Branch::all();
        $products = Product::where('status', true)->get();
        $purchaseOrders = PurchaseOrder::with(['supplier', 'branch', 'items.product'])->orderBy('ordered_at', 'desc')->get();

        return view('livewire.purchase-manager', compact('suppliers', 'branches', 'products', 'purchaseOrders'));
    }
}
