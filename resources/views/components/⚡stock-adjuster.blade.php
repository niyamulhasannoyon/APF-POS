<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $productId = null;
    public $productName = '';
    public $branchId = '';
    public $quantity = '';
    public $isOpen = false;
    public $branches = [];

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
        $this->quantity = '';
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
        ]);

        DB::table('branch_product')
            ->updateOrInsert(
                ['branch_id' => $this->branchId, 'product_id' => $this->productId],
                ['stock_quantity' => $this->quantity, 'updated_at' => now()]
            );

        $this->close();
        
        // Dispatch event to reload parent page
        $this->dispatch('stock-updated');
        
        session()->flash('success', 'Stock level adjusted successfully!');
    }
};
?>

<div x-data="{ show: @entangle('isOpen') }">
    <!-- Modal Backdrop & Panel -->
    <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
        <div @click.away="$wire.close()" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
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
                    
                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">New Stock Quantity *</label>
                        <input type="number" wire:model="quantity" required min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
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