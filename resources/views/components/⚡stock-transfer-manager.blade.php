<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $fromBranchId = '';
    public $toBranchId = '';
    public $productId = '';
    public $quantity = '';
    public $notes = '';

    public $transfers = [];
    public $branches = [];
    public $products = [];

    public function mount()
    {
        $this->branches = \App\Models\Branch::all();
        $this->products = \App\Models\Product::where('status', true)->get();
        if (count($this->branches) > 1) {
            $this->fromBranchId = $this->branches[0]->id;
            $this->toBranchId = $this->branches[1]->id;
        }
        if (count($this->products) > 0) {
            $this->productId = $this->products[0]->id;
        }
        $this->loadTransfers();
    }

    public function loadTransfers()
    {
        $this->transfers = \App\Models\StockTransfer::with(['fromBranch', 'toBranch', 'items.product', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createTransfer()
    {
        $this->validate([
            'fromBranchId' => 'required|exists:branches,id',
            'toBranchId' => 'required|exists:branches,id|different:fromBranchId',
            'productId' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () {
            $transfer = \App\Models\StockTransfer::create([
                'from_branch_id' => $this->fromBranchId,
                'to_branch_id' => $this->toBranchId,
                'status' => 'pending',
                'user_id' => auth()->id() ?? 1,
                'notes' => $this->notes
            ]);

            \App\Models\StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $this->productId,
                'quantity_requested' => $this->quantity,
                'quantity_transferred' => 0
            ]);
        });

        $this->quantity = '';
        $this->notes = '';
        $this->loadTransfers();
        session()->flash('success', 'Stock transfer request created successfully.');
    }

    public function shipTransfer($id)
    {
        $transfer = \App\Models\StockTransfer::with('items')->findOrFail($id);
        if ($transfer->status !== 'pending') return;

        // Check stock availability at source branch
        foreach ($transfer->items as $item) {
            $sourceStock = DB::table('branch_product')
                ->where('branch_id', $transfer->from_branch_id)
                ->where('product_id', $item->product_id)
                ->value('stock_quantity') ?? 0;

            if ($sourceStock < $item->quantity_requested) {
                session()->flash('error', "Insufficient stock at source branch for product: {$item->product->name} (Available: {$sourceStock})");
                return;
            }
        }

        DB::transaction(function () use ($transfer) {
            // Deduct stock from source branch
            foreach ($transfer->items as $item) {
                DB::table('branch_product')
                    ->where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity_requested);

                $item->update(['quantity_transferred' => $item->quantity_requested]);
            }

            $transfer->update([
                'status' => 'shipped',
                'shipped_at' => now()
            ]);
        });

        $this->loadTransfers();
        session()->flash('success', 'Stock transfer shipped successfully.');
    }

    public function receiveTransfer($id)
    {
        $transfer = \App\Models\StockTransfer::with('items')->findOrFail($id);
        if ($transfer->status !== 'shipped') return;

        DB::transaction(function () use ($transfer) {
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
                        'updated_at' => now()
                    ]);
                }
            }

            $transfer->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        });

        $this->loadTransfers();
        session()->flash('success', 'Stock transfer received and finalized.');
    }

    public function cancelTransfer($id)
    {
        $transfer = \App\Models\StockTransfer::with('items')->findOrFail($id);
        if (in_array($transfer->status, ['completed', 'cancelled'])) return;

        DB::transaction(function () use ($transfer) {
            // If already shipped, return stock back to source branch
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

        $this->loadTransfers();
        session()->flash('success', 'Stock transfer cancelled.');
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Initiate Stock Transfer Request -->
    <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-150 h-fit">
        <h4 class="text-base font-bold text-gray-900 mb-4 flex items-center">
            🚚 Request Stock Transfer
        </h4>
        
        @if (session()->has('error'))
            <div class="mb-4 p-3 bg-rose-100 border-l-4 border-rose-500 text-rose-800 text-xs font-semibold rounded-r">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="createTransfer" class="space-y-4">
            <!-- Source Branch -->
            <div>
                <label class="block text-xs font-semibold text-gray-700">Source Branch (From) *</label>
                <select wire:model="fromBranchId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Branch -->
            <div>
                <label class="block text-xs font-semibold text-gray-700">Destination Branch (To) *</label>
                <select wire:model="toBranchId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Product selection -->
            <div>
                <label class="block text-xs font-semibold text-gray-700">Select Product *</label>
                <select wire:model="productId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-xs font-semibold text-gray-700">Quantity to Transfer *</label>
                <input type="number" wire:model="quantity" required min="1" step="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-semibold text-gray-700">Notes / Instructions</label>
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm" placeholder="Specify logistics details..."></textarea>
            </div>

            <button type="submit" class="w-full px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow transition text-xs">
                Submit Request
            </button>
        </form>
    </div>

    <!-- Right: Active Transfers Ledger -->
    <div class="lg:col-span-2 bg-white p-6 shadow-sm sm:rounded-lg border border-gray-150">
        <h4 class="text-base font-bold text-gray-900 mb-4">
            📋 Stock Transfers Log
        </h4>

        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
            @forelse($transfers as $trans)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition hover:bg-gray-100/50">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold font-mono bg-gray-200 text-gray-700 px-2 py-0.5 rounded">#ST-{{ $trans->id }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold uppercase text-[9px]" :class="{
                                'bg-amber-100 text-amber-800': '{{ $trans->status }}' === 'pending',
                                'bg-blue-100 text-blue-800': '{{ $trans->status }}' === 'shipped',
                                'bg-emerald-100 text-emerald-800': '{{ $trans->status }}' === 'completed',
                                'bg-rose-100 text-rose-800': '{{ $trans->status }}' === 'cancelled'
                            }">
                                {{ $trans->status }}
                            </span>
                        </div>
                        
                        <div class="text-sm font-semibold text-gray-800">
                            {{ $trans->fromBranch->name }} ➔ {{ $trans->toBranch->name }}
                        </div>

                        <!-- Item details -->
                        @foreach($trans->items as $item)
                            <div class="text-xs text-gray-600 font-medium">
                                • {{ $item->product->name }} (Requested: <strong class="text-gray-800">{{ $item->quantity_requested }}</strong>, Transferred: <strong class="text-gray-800">{{ $item->quantity_transferred }}</strong>)
                            </div>
                        @endforeach

                        <div class="text-[10px] text-gray-500">
                            Requested by: <strong>{{ $trans->user->name }}</strong> on {{ $trans->created_at->format('M d, Y h:ia') }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 flex-wrap select-none md:self-center">
                        @if($trans->status === 'pending')
                            <button wire:click="shipTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition shadow-sm">
                                Ship Items
                            </button>
                            <button wire:click="cancelTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-750 rounded text-xs font-semibold transition">
                                Cancel
                            </button>
                        @elseif($trans->status === 'shipped')
                            <button wire:click="receiveTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition shadow-sm">
                                Receive Items
                            </button>
                            <button wire:click="cancelTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-xs font-bold border border-rose-200 transition">
                                Return / Cancel
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-xs text-gray-500 py-12">
                    No branch transfers initiated yet.
                </div>
            @endforelse
        </div>
    </div>
</div>