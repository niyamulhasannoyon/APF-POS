<div class="space-y-6">
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4 w-full sm:w-auto">
            <div class="relative flex-1 sm:w-64">
                <input type="text" wire:model.debounce.250ms="search" placeholder="Search name, contact, phone..." class="w-full text-xs">
            </div>
            <select wire:model="statusFilter" class="text-xs">
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>
        <button wire:click="openCreate" class="btn-indigo whitespace-nowrap">
            ➕ Add Supplier
        </button>
    </div>

    <!-- Suppliers List -->
    <div class="glass-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Supplier Name</th>
                        <th class="py-3 px-4">Contact Person</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Terms</th>
                        <th class="py-3 px-4 text-right">Outstanding Bal</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3.5 px-4 text-sm font-semibold text-slate-200">{{ $supplier->name }}</td>
                            <td class="py-3.5 px-4 text-sm text-slate-300">{{ $supplier->contact_name ?? 'N/A' }}</td>
                            <td class="py-3.5 px-4 text-xs font-mono text-slate-400">{{ $supplier->phone ?? 'N/A' }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-400">{{ $supplier->email ?? 'N/A' }}</td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-indigo-400">{{ $supplier->payment_terms }}</td>
                            <td class="py-3.5 px-4 text-sm font-bold text-slate-100 text-right font-mono">
                                ${{ number_format($supplier->outstanding_balance, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button wire:click="toggleStatus({{ $supplier->id }})" class="focus:outline-none">
                                    @if ($supplier->status)
                                        <span class="badge-emerald cursor-pointer">Active</span>
                                    @else
                                        <span class="badge-rose cursor-pointer">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1">
                                <button wire:click="openEdit({{ $supplier->id }})" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded text-[11px] font-semibold transition">
                                    Edit
                                </button>
                                <button wire:click="openPayModal({{ $supplier->id }})" class="px-2 py-1 bg-emerald-600/10 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/20 rounded text-[11px] font-semibold transition">
                                    Pay 💵
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-xs text-slate-500">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[480px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ $supplierId ? 'Edit Supplier' : 'Add New Supplier' }}</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                <form wire:submit.prevent="saveSupplier" class="p-5 space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Supplier Name</label>
                        <input type="text" wire:model.defer="name" class="w-full" placeholder="e.g. Acme Corp">
                        @error('name') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Contact Person</label>
                        <input type="text" wire:model.defer="contactName" class="w-full" placeholder="Contact Name">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-400">Email Address</label>
                            <input type="email" wire:model.defer="email" class="w-full" placeholder="supplier@email.com">
                            @error('email') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-400">Phone Number</label>
                            <input type="text" wire:model.defer="phone" class="w-full" placeholder="+8801700000000">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Postal Address</label>
                        <textarea wire:model.defer="address" class="w-full" rows="2" placeholder="Full Address"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-400">Payment Terms</label>
                            <select wire:model.defer="paymentTerms" class="w-full">
                                <option value="COD">Cash On Delivery (COD)</option>
                                <option value="Net 15">Net 15</option>
                                <option value="Net 30">Net 30</option>
                                <option value="Net 60">Net 60</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-400">Starting Outstanding Balance</label>
                            <input type="number" wire:model.defer="outstandingBalance" class="w-full text-right" step="0.01" min="0" {{ $supplierId ? 'disabled' : '' }}>
                            @error('outstandingBalance') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" wire:model.defer="status" id="supplier_status_check" class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30">
                        <label for="supplier_status_check" class="text-xs font-semibold text-slate-350">Active Supplier</label>
                    </div>

                    <div class="pt-4 border-t border-slate-700 flex gap-3">
                        <button type="submit" class="btn-indigo flex-1">Save Supplier</button>
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Payment Modal -->
    @if ($showPaymentModal)
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[380px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">Pay Supplier</h3>
                    <button wire:click="$set('showPaymentModal', false)" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                <form wire:submit.prevent="submitPayment" class="p-5 space-y-4">
                    <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Supplier</p>
                        <p class="text-sm font-bold text-slate-200">{{ $selectedSupplierForPayment->name }}</p>
                        <p class="text-xs text-slate-400 mt-1 font-mono">Current Balance: ${{ number_format($selectedSupplierForPayment->outstanding_balance, 2) }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Payment Amount ($)</label>
                        <input type="number" wire:model.defer="payAmount" class="w-full text-right font-mono" step="0.01" min="0.01" max="{{ $selectedSupplierForPayment->outstanding_balance }}">
                        @error('payAmount') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Payment Method</label>
                        <select wire:model.defer="payMethod" class="w-full">
                            <option value="cash">💵 Cash</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="check">✍️ Check</option>
                            <option value="other">📱 Mobile Banking</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Notes</label>
                        <input type="text" wire:model.defer="payNotes" class="w-full" placeholder="Reference details, transaction ID...">
                    </div>

                    <div class="pt-4 border-t border-slate-700 flex gap-3">
                        <button type="submit" class="btn-emerald flex-1">Record Payment</button>
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
