<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $productId = null;
    public $productName = '';
    public $variantId = ''; // selected variant (optional)
    public $branchId = '';
    public $quantity = '';
    public $reasonCode = 'correction';
    public $notes = '';
    public $isOpen = false;
    public $branches = [];
    public $productVariantsList = [];

    public function mount()
    {
        $this->branches = \App\Models\Branch::all();
        if (count($this->branches) > 0) {
            $this->branchId = $this->branches[0]->id;
        }
    }

    #[On('open-stock-adjuster')]
    public function open($productId, $productName)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->variantId = '';
        $this->quantity = '';
        $this->reasonCode = 'correction';
        $this->notes = '';
        
        // Fetch variants
        $this->productVariantsList = \App\Models\ProductVariant::where('product_id', $productId)->get();
        if (count($this->productVariantsList) > 0) {
            $this->variantId = $this->productVariantsList[0]->id;
        }

        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
    }

    public function save()
    {
        $this->validate([
            'productId' => 'required|exists:products,id',
            'branchId' => 'required|exists:branches,id',
            'quantity' => 'required|numeric|min:0',
            'reasonCode' => 'required|string|in:correction,damage,theft,expiry,conversion',
            'notes' => 'nullable|string|max:500'
        ]);

        $delta = 0.00;

        if ($this->variantId) {
            // Find current stock
            $currentStock = DB::table('branch_variant')
                ->where('branch_id', $this->branchId)
                ->where('product_variant_id', $this->variantId)
                ->value('stock_quantity') ?? 0.00;

            $delta = $this->quantity - $currentStock;

            // Save variant stock level
            DB::table('branch_variant')
                ->updateOrInsert(
                    ['branch_id' => $this->branchId, 'product_variant_id' => $this->variantId],
                    ['stock_quantity' => $this->quantity, 'updated_at' => now()]
                );
        } else {
            // Find current stock
            $currentStock = DB::table('branch_product')
                ->where('branch_id', $this->branchId)
                ->where('product_id', $this->productId)
                ->value('stock_quantity') ?? 0.00;

            $delta = $this->quantity - $currentStock;

            // Save parent stock level
            DB::table('branch_product')
                ->updateOrInsert(
                    ['branch_id' => $this->branchId, 'product_id' => $this->productId],
                    ['stock_quantity' => $this->quantity, 'updated_at' => now()]
                );
        }

        // Log to stock adjustments history
        \App\Models\StockAdjustment::create([
            'branch_id' => $this->branchId,
            'product_id' => $this->productId,
            'product_variant_id' => $this->variantId ?: null,
            'user_id' => auth()->id() ?? 1,
            'quantity_adjusted' => $delta,
            'reason_code' => $this->reasonCode,
            'notes' => $this->notes
        ]);

        $this->close();
        
        $this->dispatch('stock-updated');
        
        session()->flash('success', 'Stock level adjusted successfully!');
    }
};
?>

<div x-data="{ show: @entangle('isOpen') }">
    <!-- Modal Backdrop & Panel -->
    <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
        <div @click.away="$wire.close()" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s_cubic-bezier(0.16,1,0.3,1)]">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900">Adjust Stock Level (Livewire)</h3>
                <button @click="$wire.close()" type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none">&times;</button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-3 rounded text-sm font-semibold text-gray-700">
                    Product: <span class="text-indigo-600">{{ $productName }}</span>
                </div>
                
                <form wire:submit.prevent="save" class="space-y-4">
                    <!-- Branch Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Select Branch *</label>
                        <select wire:model="branchId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Variant Selection (if any) -->
                    @if(count($productVariantsList) > 0)
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Select Variant *</label>
                            <select wire:model="variantId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                @foreach($productVariantsList as $var)
                                    <option value="{{ $var->id }}">
                                        {{ implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($var->option_values), $var->option_values)) }} (SKU: {{ $var->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Reason Code Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Reason Code *</label>
                        <select wire:model="reasonCode" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                            <option value="correction">Stock Count Correction</option>
                            <option value="damage">Damaged Goods</option>
                            <option value="theft">Theft / Stolen Goods</option>
                            <option value="expiry">Expired Stock Removal</option>
                            <option value="conversion">Unit Conversion Adjustments</option>
                        </select>
                    </div>
                    
                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">New Stock Quantity *</label>
                        <input type="number" wire:model="quantity" required min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Reason Details / Notes</label>
                        <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm" placeholder="Optional audit details..."></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="$wire.close()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded font-semibold text-sm transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold shadow-md transition text-sm">Save Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>