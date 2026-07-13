<div class="space-y-6">
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-slate-100">Inter-Branch Stock Transfers</h3>
        <button wire:click="$toggle('showCreateForm')" class="btn-indigo">
            {{ $showCreateForm ? 'Cancel Request' : '⚡ New Transfer Request' }}
        </button>
    </div>

    @if ($showCreateForm)
        <div class="glass-card max-w-3xl">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">Request Stock Transfer</h4>
            <form wire:submit.prevent="createTransfer" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Source Branch (From)</label>
                        <select wire:model.defer="fromBranchId" class="w-full">
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('fromBranchId') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Destination Branch (To)</label>
                        <select wire:model.defer="toBranchId" class="w-full">
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('toBranchId') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-400">Notes / Reason</label>
                    <textarea wire:model.defer="notes" placeholder="Why are you transferring this stock?" rows="2" class="w-full"></textarea>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center border-t border-slate-800/80 pt-3">
                        <span class="text-xs font-bold text-slate-400 uppercase">Transfer Items</span>
                        <button type="button" wire:click="addTransferItem" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 border border-slate-700 rounded text-[11px] font-bold transition">
                            + Add Item
                        </button>
                    </div>

                    @if (empty($items))
                        <p class="text-xs text-slate-500 py-2">No items added yet. Click "+ Add Item" above.</p>
                    @endif

                    @foreach ($items as $index => $item)
                        <div class="flex gap-3 items-end bg-slate-950/20 p-3 rounded-lg border border-slate-800/60" wire:key="transfer-item-{{ $index }}">
                            <div class="flex-1 flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Product</label>
                                <select wire:model.defer="items.{{ $index }}.product_id" class="w-full text-xs">
                                    <option value="">Choose Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} [{{ $product->sku }}]</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="w-32 flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Qty</label>
                                <input type="number" wire:model.defer="items.{{ $index }}.qty" class="w-full text-xs text-right font-mono" step="0.01" min="0.01">
                            </div>

                            <button type="button" wire:click="removeTransferItem({{ $index }})" class="p-2.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 hover:border-rose-500/30 text-rose-400 rounded-lg text-xs font-extrabold select-none cursor-pointer">
                                Remove
                            </button>
                        </div>
                    @endforeach
                    @error('items') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-slate-800/80 flex gap-3">
                    <button type="submit" class="btn-indigo">Submit Transfer Request</button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="glass-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">From Branch</th>
                        <th class="py-3 px-4">To Branch</th>
                        <th class="py-3 px-4">Created By</th>
                        <th class="py-3 px-4">Items Count</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse ($transfers as $transfer)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3.5 px-4 text-xs font-mono text-slate-400">{{ $transfer->created_at->format('M d, Y h:i A') }}</td>
                            <td class="py-3.5 px-4 text-sm font-semibold text-slate-200">{{ $transfer->fromBranch->name }}</td>
                            <td class="py-3.5 px-4 text-sm font-semibold text-slate-200">{{ $transfer->toBranch->name }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-350">{{ $transfer->user->name }}</td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-400">
                                {{ $transfer->items->count() }} items ({{ $transfer->items->sum('quantity_requested') }} qty)
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($transfer->status === 'pending')
                                    <span class="badge-amber">Pending</span>
                                @elseif ($transfer->status === 'shipped')
                                    <span class="badge-primary">Shipped</span>
                                @elseif ($transfer->status === 'completed')
                                    <span class="badge-emerald">Completed</span>
                                @elseif ($transfer->status === 'cancelled')
                                    <span class="badge-rose">Cancelled</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5">
                                @if ($transfer->status === 'pending')
                                    <button wire:click="shipTransfer({{ $transfer->id }})" class="px-2.5 py-1.5 bg-indigo-600/10 hover:bg-indigo-600 text-indigo-400 hover:text-white border border-indigo-500/20 rounded text-xs font-bold transition">
                                        Ship 🚚
                                    </button>
                                    <button wire:click="cancelTransfer({{ $transfer->id }})" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 rounded text-xs font-bold transition">
                                        Cancel
                                    </button>
                                @elseif ($transfer->status === 'shipped')
                                    <button wire:click="receiveTransfer({{ $transfer->id }})" class="px-2.5 py-1.5 bg-emerald-600/10 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/20 rounded text-xs font-bold transition">
                                        Receive & Complete ✅
                                    </button>
                                    <button wire:click="cancelTransfer({{ $transfer->id }})" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 rounded text-xs font-bold transition">
                                        Cancel & Revert
                                    </button>
                                @else
                                    <span class="text-xs text-slate-500 font-medium">No actions</span>
                                @endif
                            </td>
                        </tr>
                        @if ($transfer->notes)
                            <tr class="bg-slate-900/5">
                                <td colspan="7" class="py-2 px-4 text-xs text-slate-500 italic">
                                    <strong>Notes:</strong> {{ $transfer->notes }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-500">No transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
