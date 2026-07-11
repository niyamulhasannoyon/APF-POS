<?php

use Livewire\Component;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // PO Form
    public $supplierId = '';
    public $branchId = '';
    public $notes = '';
    public $paidAmount = 0.00;

    // Item Form (Adding to draft)
    public $selectedProductId = '';
    public $qtyOrdered = 1;
    public $unitCost = 0.00;
    public $draftItems = []; // Array of ['product_id', 'product_name', 'qty', 'cost']

    // UI state
    public $isCreateOpen = false;
    public $isReceiveOpen = false;
    
    // Receiving Modal
    public $receivePoId = null;
    public $receiveItems = []; // Array of ['item_id', 'product_name', 'qty_ordered', 'qty_received']

    public $suppliers = [];
    public $branches = [];
    public $products = [];
    public $ordersList = [];

    public function mount()
    {
        $this->suppliers = Supplier::where('status', true)->get();
        $this->branches = Branch::where('status', true)->get();
        $this->products = Product::where('status', true)->get();

        if (count($this->suppliers) > 0) $this->supplierId = $this->suppliers[0]->id;
        if (count($this->branches) > 0) $this->branchId = $this->branches[0]->id;
        if (count($this->products) > 0) {
            $this->selectedProductId = $this->products[0]->id;
            $this->unitCost = $this->products[0]->cost;
        }

        $this->loadOrders();
    }

    public function updatedSelectedProductId($value)
    {
        $product = Product::find($value);
        if ($product) {
            $this->unitCost = $product->cost;
        }
    }

    public function loadOrders()
    {
        $this->ordersList = PurchaseOrder::with(['supplier', 'branch', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreate()
    {
        $this->draftItems = [];
        $this->notes = '';
        $this->paidAmount = 0.00;
        $this->isCreateOpen = true;
    }

    public function closeCreate()
    {
        $this->isCreateOpen = false;
    }

    public function addToDraft()
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'qtyOrdered' => 'required|numeric|min:0.01',
            'unitCost' => 'required|numeric|min:0.00'
        ]);

        $product = Product::find($this->selectedProductId);

        // Check if already in draft
        foreach ($this->draftItems as $index => $item) {
            if ($item['product_id'] == $this->selectedProductId) {
                $this->draftItems[$index]['qty'] += $this->qtyOrdered;
                return;
            }
        }

        $this->draftItems[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'qty' => $this->qtyOrdered,
            'cost' => $this->unitCost
        ];
    }

    public function removeFromDraft($index)
    {
        unset($this->draftItems[$index]);
        $this->draftItems = array_values($this->draftItems);
    }

    public function getDraftTotal()
    {
        $total = 0.00;
        foreach ($this->draftItems as $item) {
            $total += $item['qty'] * $item['cost'];
        }
        return $total;
    }

    public function savePurchaseOrder()
    {
        if (empty($this->draftItems)) {
            session()->flash('error', 'Please add at least one item to the purchase order.');
            return;
        }

        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'branchId' => 'required|exists:branches,id',
            'paidAmount' => 'required|numeric|min:0.00',
            'notes' => 'nullable|string|max:500'
        ]);

        $totalAmount = $this->getDraftTotal();

        if ($this->paidAmount > $totalAmount) {
            $this->addError('paidAmount', 'Paid amount cannot exceed the total PO amount.');
            return;
        }

        DB::transaction(function () use ($totalAmount) {
            $paymentStatus = 'unpaid';
            if ($this->paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            } elseif ($this->paidAmount > 0) {
                $paymentStatus = 'partially_paid';
            }

            $po = PurchaseOrder::create([
                'supplier_id' => $this->supplierId,
                'branch_id' => $this->branchId,
                'status' => 'ordered',
                'total_amount' => $totalAmount,
                'paid_amount' => $this->paidAmount,
                'payment_status' => $paymentStatus,
                'notes' => $this->notes,
                'ordered_at' => now()
            ]);

            foreach ($this->draftItems as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['qty'],
                    'quantity_received' => 0.00,
                    'unit_cost' => $item['cost'],
                    'subtotal' => $item['qty'] * $item['cost']
                ]);
            }
        });

        $this->isCreateOpen = false;
        $this->loadOrders();
        session()->flash('success', 'Purchase order submitted successfully.');
    }

    public function openReceiveModal($poId)
    {
        $po = PurchaseOrder::with('items.product')->findOrFail($poId);
        $this->receivePoId = $po->id;
        $this->receiveItems = [];

        foreach ($po->items as $item) {
            $this->receiveItems[] = [
                'item_id' => $item->id,
                'product_name' => $item->product->name,
                'product_id' => $item->product_id,
                'qty_ordered' => $item->quantity_ordered,
                'qty_received' => $item->quantity_ordered // default to match ordered count
            ];
        }

        $this->isReceiveOpen = true;
    }

    public function closeReceive()
    {
        $this->isReceiveOpen = false;
    }

    public function submitReceive()
    {
        $po = PurchaseOrder::with('items')->findOrFail($this->receivePoId);
        if ($po->status === 'completed') return;

        DB::transaction(function () use ($po) {
            foreach ($this->receiveItems as $recItem) {
                $orderItem = PurchaseOrderItem::findOrFail($recItem['item_id']);
                $orderItem->update([
                    'quantity_received' => $recItem['qty_received']
                ]);

                // Increment branch stock level
                $exists = DB::table('branch_product')
                    ->where('branch_id', $po->branch_id)
                    ->where('product_id', $recItem['product_id'])
                    ->exists();

                if ($exists) {
                    DB::table('branch_product')
                        ->where('branch_id', $po->branch_id)
                        ->where('product_id', $recItem['product_id'])
                        ->increment('stock_quantity', $recItem['qty_received']);
                } else {
                    DB::table('branch_product')->insert([
                        'branch_id' => $po->branch_id,
                        'product_id' => $recItem['product_id'],
                        'stock_quantity' => $recItem['qty_received'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Calculate unpaid balance and add to supplier outstanding
            $unpaid = $po->total_amount - $po->paid_amount;
            if ($unpaid > 0) {
                $po->supplier->increment('outstanding_balance', $unpaid);
            }

            $po->update([
                'status' => 'completed'
            ]);
        });

        $this->isReceiveOpen = false;
        $this->loadOrders();
        session()->flash('success', 'Goods received. Stock levels and supplier dues updated.');
    }

    public function cancelOrder($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status !== 'ordered') return;

        $po->update(['status' => 'cancelled']);
        $this->loadOrders();
        session()->flash('success', 'Purchase order cancelled.');
    }
};
?>

<div>
    <!-- Top Action Bar -->
    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex flex-col md:flex-row gap-4 items-center justify-between mb-6">
        <h4 class="text-base font-bold text-gray-800">📦 Purchase Orders Dashboard</h4>
        <button wire:click="openCreate" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-sm transition duration-150 text-xs">
            + New Purchase Order
        </button>
    </div>

    <!-- Active Purchases Ledger -->
    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                <tr>
                    <th class="px-6 py-3">PO Number</th>
                    <th class="px-6 py-3">Supplier</th>
                    <th class="px-6 py-3">Destination Branch</th>
                    <th class="px-6 py-3">Total Amount</th>
                    <th class="px-6 py-3">Paid Amount</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($ordersList as $po)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-mono font-bold text-gray-800">#PO-{{ $po->id }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $po->supplier->name }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $po->branch->name }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">${{ number_format($po->total_amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-650">${{ number_format($po->paid_amount, 2) }}</td>
                        <td class="px-6 py-4 select-none">
                            <span class="text-xs px-2.5 py-1 rounded-full font-bold uppercase text-[9px]" :class="{
                                'bg-amber-100 text-amber-800': '{{ $po->status }}' === 'ordered',
                                'bg-emerald-100 text-emerald-800': '{{ $po->status }}' === 'completed',
                                'bg-rose-100 text-rose-800': '{{ $po->status }}' === 'cancelled'
                            }">
                                {{ $po->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            @if($po->status === 'ordered')
                                <button wire:click="openReceiveModal({{ $po->id }})" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition">
                                    Receive Goods
                                </button>
                                <button wire:click="cancelOrder({{ $po->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition">
                                    Cancel
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-12 text-xs">No purchase orders created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create Purchase Order Modal -->
    <div x-data="{ show: @entangle('isCreateOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closeCreate()" class="bg-white rounded-lg shadow-xl w-full max-w-3xl overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Create Purchase Order</h3>
                    <button @click="$wire.closeCreate()" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Left: Metadata Form -->
                    <div class="space-y-4 border-r border-gray-100 pr-0 md:pr-6 md:col-span-1">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Supplier *</label>
                            <select wire:model="supplierId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Destination Branch *</label>
                            <select wire:model="branchId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Paid Amount ($)</label>
                            <input type="number" wire:model="paidAmount" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Notes / Remarks</label>
                            <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <button type="button" wire:click="savePurchaseOrder" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow transition text-xs">
                            Submit Purchase Order
                        </button>
                    </div>

                    <!-- Right: Product Selector & Line Items List -->
                    <div class="md:col-span-2 space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Select Product</label>
                                <select wire:model.live="selectedProductId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Unit Cost ($)</label>
                                <input type="number" wire:model="unitCost" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                            </div>
                            <div class="flex gap-2">
                                <div class="w-20">
                                    <label class="block text-xs font-semibold text-gray-700">Qty</label>
                                    <input type="number" wire:model="qtyOrdered" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                </div>
                                <button type="button" wire:click="addToDraft" class="flex-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded shadow text-xs">
                                    Add
                                </button>
                            </div>
                        </div>

                        <!-- Draft Items Ledger -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden max-h-[220px] overflow-y-auto">
                            <table class="min-w-full text-left text-xs divide-y divide-gray-150">
                                <thead class="bg-gray-50 text-gray-650 font-bold uppercase text-[9px]">
                                    <tr>
                                        <th class="px-4 py-2">Product</th>
                                        <th class="px-4 py-2">Qty</th>
                                        <th class="px-4 py-2">Cost</th>
                                        <th class="px-4 py-2">Subtotal</th>
                                        <th class="px-4 py-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($draftItems as $index => $item)
                                        <tr>
                                            <td class="px-4 py-2 font-semibold text-gray-900">{{ $item['product_name'] }}</td>
                                            <td class="px-4 py-2 text-gray-700">{{ $item['qty'] }}</td>
                                            <td class="px-4 py-2 text-gray-700">${{ number_format($item['cost'], 2) }}</td>
                                            <td class="px-4 py-2 font-bold text-gray-850">${{ number_format($item['qty'] * $item['cost'], 2) }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <button type="button" wire:click="removeFromDraft({{ $index }})" class="text-rose-600 hover:text-rose-800 font-semibold">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-gray-400 py-6">No items added to draft list.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Subtotal -->
                        <div class="text-right text-sm font-bold text-gray-850">
                            Total Order Amount: <span class="text-indigo-650 font-black text-base">${{ number_format($this->getDraftTotal(), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Goods Modal -->
    <div x-data="{ show: @entangle('isReceiveOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closeReceive()" class="bg-white rounded-lg shadow-xl w-full max-w-lg overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Verify Goods Receiving</h3>
                    <button @click="$wire.closeReceive()" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <table class="min-w-full text-left text-xs divide-y divide-gray-150">
                        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[9px]">
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2">Qty Ordered</th>
                                <th class="px-4 py-2">Qty Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($receiveItems as $index => $rec)
                                <tr>
                                    <td class="px-4 py-2 font-semibold text-gray-900">{{ $rec['product_name'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $rec['qty_ordered'] }}</td>
                                    <td class="px-4 py-2">
                                        <input type="number" wire:model="receiveItems.{{ $index }}.qty_received" min="0" step="0.01" class="w-24 rounded border-gray-300 text-xs py-1">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="$wire.closeReceive()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-750 rounded font-semibold text-xs transition">Cancel</button>
                        <button type="button" wire:click="submitReceive" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold transition text-xs">Verify & Update Stock</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>