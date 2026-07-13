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
    <div class="glass-card h-fit">
        <h4 class="text-base font-extrabold text-slate-100 mb-4 flex items-center gap-2">
            🚚 Request Stock Transfer
        </h4>
        
        @if (session()->has('error'))
            <div class="mb-4 p-3.5 bg-rose-500/10 border-l-4 border-rose-500 text-rose-455 text-xs font-bold rounded-r-lg border border-rose-500/20">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="createTransfer" class="space-y-4">
            <!-- Source Branch -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Source Branch (From) *</label>
                <select wire:model="fromBranchId" required class="block w-full">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Branch -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Destination Branch (To) *</label>
                <select wire:model="toBranchId" required class="block w-full">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Product selection -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Select Product *</label>
                <select wire:model="productId" required class="block w-full">
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Quantity to Transfer *</label>
                <input type="number" wire:model="quantity" required min="1" step="1" class="block w-full font-mono">
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Notes / Instructions</label>
                <textarea wire:model="notes" rows="2" class="block w-full" placeholder="Specify logistics details..."></textarea>
            </div>

            <button type="submit" class="w-full px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition text-xs">
                Submit Request
            </button>
        </form>
    </div>

    <!-- Right: Active Transfers Ledger -->
    <div class="lg:col-span-2 glass-card">
        <h4 class="text-base font-extrabold text-slate-100 mb-4">
            📋 Stock Transfers Log
        </h4>

        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
            @forelse($transfers as $trans)
                <div class="bg-slate-900/40 border border-slate-800/80 rounded-lg p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition hover:bg-slate-900/60">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold font-mono bg-slate-800 text-slate-300 border border-slate-700/80 px-2 py-0.5 rounded-md">#ST-{{ $trans->id }}</span>
                            <span class="text-[9px] px-2 py-0.5 rounded-md font-extrabold uppercase border" :class="{
                                'bg-amber-500/10 text-amber-300 border-amber-500/20': '{{ $trans->status }}' === 'pending',
                                'bg-indigo-500/10 text-indigo-300 border-indigo-500/20': '{{ $trans->status }}' === 'shipped',
                                'bg-emerald-500/10 text-emerald-300 border-emerald-500/20': '{{ $trans->status }}' === 'completed',
                                'bg-rose-500/10 text-rose-300 border-rose-500/20': '{{ $trans->status }}' === 'cancelled'
                            }">
                                {{ $trans->status }}
                            </span>
                        </div>
                        
                        <div class="text-sm font-bold text-slate-200">
                            {{ $trans->fromBranch->name }} ➔ {{ $trans->toBranch->name }}
                        </div>

                        <!-- Item details -->
                        @foreach($trans->items as $item)
                            <div class="text-xs text-slate-400 font-semibold">
                                • {{ $item->product->name }} (Requested: <strong class="text-slate-200">{{ $item->quantity_requested }}</strong>, Transferred: <strong class="text-slate-200">{{ $item->quantity_transferred }}</strong>)
                            </div>
                        @endforeach

                        <div class="text-[10px] text-slate-500">
                            Requested by: <strong class="text-slate-400">{{ $trans->user->name }}</strong> on {{ $trans->created_at->format('M d, Y h:ia') }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 flex-wrap select-none md:self-center">
                        @if($trans->status === 'pending')
                            <button wire:click="shipTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-650/15 text-xs transition duration-150">
                                Ship Items
                            </button>
                            <button wire:click="cancelTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-bold transition duration-150">
                                Cancel
                            </button>
                        @elseif($trans->status === 'shipped')
                            <button wire:click="receiveTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold shadow-lg shadow-emerald-600/15 text-xs transition duration-150">
                                Receive Items
                            </button>
                            <button wire:click="cancelTransfer({{ $trans->id }})" class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-lg text-xs font-bold border border-rose-500/20 shadow-lg shadow-rose-500/5 transition duration-150">
                                Return / Cancel
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-xs text-slate-550 py-12">
                    No branch transfers initiated yet.
                </div>
            @endforelse
    </div>
</div>
</div>