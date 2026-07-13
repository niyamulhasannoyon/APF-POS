<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-extrabold text-slate-100 flex items-center gap-2">📦 Stock Purchase Orders</h2>
            <p class="text-xs text-slate-400 mt-1">Replenish product stocks via supplier Purchase Orders</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="btn-indigo">
            {{ $showCreateForm ? 'Cancel Request' : '⚡ New Purchase Order' }}
        </button>
    </div>

    @if ($showCreateForm)
        <div class="glass-card max-w-4xl mb-6">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">Create Purchase Order Draft</h4>
            <form wire:submit.prevent="savePurchaseOrder" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Supplier</label>
                        <select wire:model.defer="supplierId" class="w-full">
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }} (Bal: ${{ number_format($supplier->outstanding_balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('supplierId') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Restock Destination Branch</label>
                        <select wire:model.defer="branchId" class="w-full">
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branchId') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-400">Purchase Notes</label>
                    <input type="text" wire:model.defer="notes" placeholder="Batch details, shipping terms..." class="w-full">
                </div>

                <!-- Add to Draft section -->
                <div class="border-t border-slate-800/80 pt-4 space-y-3">
                    <h5 class="text-xs font-bold text-slate-400 uppercase font-mono">Add Product to Order</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                        <div class="sm:col-span-2 flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase">Product</label>
                            <select wire:model="selectedProductId" class="w-full text-xs">
                                <option value="">Choose Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} (Cost: ${{ number_format($product->cost, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase">Quantity</label>
                            <input type="number" wire:model.defer="qtyOrdered" class="w-full text-xs text-right font-mono" step="0.01" min="0.01">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase">Unit Cost ($)</label>
                            <input type="number" wire:model.defer="unitCost" class="w-full text-xs text-right font-mono" step="0.01" min="0">
                        </div>
                    </div>
                    <button type="button" wire:click="addToDraft" class="w-full bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 border border-slate-700 py-2 rounded text-xs font-bold transition">
                        ➕ Add Line Item to PO Draft
                    </button>
                </div>

                <!-- Draft Items Table -->
                <div class="space-y-2">
                    <h5 class="text-xs font-bold text-slate-400 uppercase font-mono">Drafted Line Items</h5>
                    @if (empty($draftItems))
                        <p class="text-xs text-slate-500 py-3 bg-slate-900/10 rounded-lg text-center border border-dashed border-slate-800/80">No draft items. Add products above.</p>
                    @else
                        <div class="overflow-x-auto border border-slate-850 rounded-lg">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-900/30 text-[10px] font-bold text-slate-500 uppercase">
                                        <th class="py-2 px-3">Product Name</th>
                                        <th class="py-2 px-3 text-right">Quantity</th>
                                        <th class="py-2 px-3 text-right">Unit Cost</th>
                                        <th class="py-2 px-3 text-right">Subtotal</th>
                                        <th class="py-2 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $draftTotal = 0; @endphp
                                    @foreach ($draftItems as $index => $item)
                                        @php $sub = (float)$item['qty'] * (float)$item['cost']; $draftTotal += $sub; @endphp
                                        <tr class="border-t border-slate-800/40 text-xs">
                                            <td class="py-2 px-3 text-slate-200 font-semibold">{{ $item['name'] }}</td>
                                            <td class="py-2 px-3 text-right font-mono text-slate-300">{{ number_format($item['qty'], 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono text-slate-350">${{ number_format($item['cost'], 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono font-semibold text-slate-250">${{ number_format($sub, 2) }}</td>
                                            <td class="py-2 px-3 text-center">
                                                <button type="button" wire:click="removeFromDraft({{ $index }})" class="text-rose-400 hover:text-rose-350 font-bold transition">Remove</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-slate-900/20 font-bold border-t border-slate-700">
                                        <td colspan="3" class="py-2 px-3 text-slate-400 uppercase text-[10px]">Total Draft Dues</td>
                                        <td class="py-2 px-3 text-right font-mono text-indigo-400">${{ number_format($draftTotal, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paid Amount input -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-800/80">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-400">Amount Paid Now ($)</label>
                                <input type="number" wire:model.defer="paidAmount" class="w-full font-mono text-right" step="0.01" min="0">
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-slate-800/80 flex gap-3">
                    <button type="submit" class="btn-indigo flex-1 font-bold" @if(empty($draftItems)) disabled @endif>Submit Purchase Order</button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="btn-secondary flex-1">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <!-- PO List -->
    <div class="glass-card">
        <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">Purchase History</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Ordered At</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Receiving Branch</th>
                        <th class="py-3 px-4">Items Summary</th>
                        <th class="py-3 px-4 text-right">Total Amount</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Payment</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse ($purchaseOrders as $po)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3.5 px-4 text-xs font-mono text-slate-400">{{ $po->ordered_at->format('M d, Y h:i A') }}</td>
                            <td class="py-3.5 px-4 text-sm font-semibold text-slate-200">{{ $po->supplier->name }}</td>
                            <td class="py-3.5 px-4 text-sm text-slate-300">{{ $po->branch->name }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-400 font-medium">
                                {{ $po->items->count() }} items ({{ $po->items->sum('quantity_ordered') }} units)
                            </td>
                            <td class="py-3.5 px-4 text-sm font-bold text-slate-100 text-right font-mono">
                                ${{ number_format($po->total_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($po->status === 'ordered')
                                    <span class="badge-amber">Ordered</span>
                                @elseif ($po->status === 'completed')
                                    <span class="badge-emerald">Received</span>
                                @elseif ($po->status === 'cancelled')
                                    <span class="badge-rose">Cancelled</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($po->payment_status === 'paid')
                                    <span class="text-[10px] font-bold text-emerald-400 uppercase">Paid</span>
                                @else
                                    <span class="text-[10px] font-bold text-amber-500 uppercase">Unpaid</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5">
                                @if ($po->status === 'ordered')
                                    <button wire:click="openReceiveModal({{ $po->id }})" class="px-2.5 py-1.5 bg-emerald-600/10 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/20 rounded text-xs font-bold transition">
                                        Receive Goods 🚚
                                    </button>
                                    <button wire:click="cancelPurchaseOrder({{ $po->id }})" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 rounded text-xs font-bold transition">
                                        Cancel
                                    </button>
                                @else
                                    <span class="text-xs text-slate-500 font-medium">Completed</span>
                                @endif
                            </td>
                        </tr>
                        @if ($po->notes)
                            <tr class="bg-slate-900/5">
                                <td colspan="8" class="py-2 px-4 text-xs text-slate-500 italic">
                                    <strong>PO Notes:</strong> {{ $po->notes }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-xs text-slate-500">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Goods Receipt Modal -->
    @if ($showReceiveModal && $selectedPoForReceive)
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[480px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">Receive Goods</h3>
                    <button wire:click="$set('showReceiveModal', false)" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                <form wire:submit.prevent="submitReceive" class="p-5 space-y-4">
                    <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                        @foreach ($receiveItems as $index => $rItem)
                            <div class="flex justify-between items-center gap-4 bg-slate-900/50 p-3 rounded-lg border border-slate-800" wire:key="recv-item-{{ $index }}">
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-slate-200">{{ $rItem['product_name'] }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono">Ordered: {{ number_format($rItem['qty_ordered'], 2) }}</p>
                                </div>
                                <div class="w-24">
                                    <input type="number" wire:model.defer="receiveItems.{{ $index }}.qty_received" class="w-full text-right text-xs" step="0.01" min="0">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="pt-4 border-t border-slate-700 flex gap-3">
                        <button type="submit" class="btn-emerald flex-1 font-bold">Submit Goods Receipt</button>
                        <button type="button" wire:click="$set('showReceiveModal', false)" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
