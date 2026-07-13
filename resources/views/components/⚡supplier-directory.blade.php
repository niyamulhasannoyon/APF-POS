<?php

use Livewire\Component;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // Supplier Form Fields
    public $supplierId = null;
    public $name = '';
    public $contactName = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $paymentTerms = 'cash';
    public $status = true;

    // Payment Form Fields
    public $paySupplierId = null;
    public $paySupplierName = '';
    public $payAmount = '';
    public $payMethod = 'cash';
    public $payNotes = '';

    // UI state
    public $search = '';
    public $isFormOpen = false;
    public $isPayOpen = false;

    public function getSuppliers()
    {
        return Supplier::where('name', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")
            ->orderBy('name', 'asc')
            ->get();
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEdit($id)
    {
        $this->resetForm();
        $supplier = Supplier::findOrFail($id);
        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->contactName = $supplier->contact_name;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->paymentTerms = $supplier->payment_terms;
        $this->status = $supplier->status;

        $this->isFormOpen = true;
    }

    public function saveSupplier()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'contactName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'paymentTerms' => 'required|string|in:cash,net15,net30,net60',
        ]);

        Supplier::updateOrCreate(
            ['id' => $this->supplierId],
            [
                'name' => $this->name,
                'contact_name' => $this->contactName,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'payment_terms' => $this->paymentTerms,
                'status' => $this->status
            ]
        );

        $this->isFormOpen = false;
        $this->resetForm();
        session()->flash('success', 'Supplier details saved successfully.');
    }

    public function openPayModal($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->paySupplierId = $supplier->id;
        $this->paySupplierName = $supplier->name;
        $this->payAmount = '';
        $this->payMethod = 'cash';
        $this->payNotes = '';
        $this->isPayOpen = true;
    }

    public function submitPayment()
    {
        $supplier = Supplier::findOrFail($this->paySupplierId);

        $this->validate([
            'payAmount' => "required|numeric|min:0.01|max:{$supplier->outstanding_balance}",
            'payMethod' => 'required|string|in:cash,bank_transfer,cheque,card',
            'payNotes' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($supplier) {
            SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'amount_paid' => $this->payAmount,
                'payment_method' => $this->payMethod,
                'notes' => $this->payNotes
            ]);

            $supplier->decrement('outstanding_balance', $this->payAmount);
        });

        $this->isPayOpen = false;
        session()->flash('success', 'Payment logged and supplier balance reduced successfully.');
    }

    public function resetForm()
    {
        $this->supplierId = null;
        $this->name = '';
        $this->contactName = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->paymentTerms = 'cash';
        $this->status = true;
    }

    public function closeForm()
    {
        $this->isFormOpen = false;
    }

    public function closePay()
    {
        $this->isPayOpen = false;
    }
};
?>

<div>
    <!-- Top Action Bar -->
    <div class="glass-card flex flex-col md:flex-row gap-4 items-center justify-between mb-6 p-4.5">
        <div class="w-full md:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by company or email..." class="block w-full">
        </div>
        <button wire:click="openCreate" class="w-full md:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 text-xs">
            + Register Supplier
        </button>
    </div>

    <!-- Suppliers Grid/Table -->
    <div class="glass-card p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-900/40">
                    <th class="px-6 py-3.5">Supplier Name</th>
                    <th class="px-6 py-3.5">Contact Person</th>
                    <th class="px-6 py-3.5">Phone & Email</th>
                    <th class="px-6 py-3.5">Payment Terms</th>
                    <th class="px-6 py-3.5">Outstanding Dues</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/40">
                @forelse($this->getSuppliers() as $sup)
                    <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                        <td class="px-6 py-4 font-bold text-slate-200">{{ $sup->name }}</td>
                        <td class="px-6 py-4 text-slate-400 font-semibold">{{ $sup->contact_name ?: '-' }}</td>
                        <td class="px-6 py-4 space-y-0.5 text-xs text-slate-450 font-mono">
                            <div>{{ $sup->phone ?: '-' }}</div>
                            <div>{{ $sup->email ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs bg-slate-800 text-slate-300 font-bold px-2.5 py-1 rounded-md border border-slate-700/80">
                                {{ strtoupper($sup->payment_terms) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold">
                            @if($sup->outstanding_balance > 0)
                                <span class="text-rose-400 font-mono">${{ number_format($sup->outstanding_balance, 2) }}</span>
                            @else
                                <span class="text-emerald-400 font-mono">$0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2.5">
                            @if($sup->outstanding_balance > 0)
                                <button wire:click="openPayModal({{ $sup->id }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-all duration-150 shadow-lg shadow-emerald-500/10">
                                    Pay Dues
                                </button>
                            @endif
                            <button wire:click="openEdit({{ $sup->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-semibold transition-all duration-150">
                                Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500 py-12 text-xs">No registered suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Supplier Form Modal -->
    <div x-data="{ show: @entangle('isFormOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closeForm()" class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-800/80 flex justify-between items-center bg-slate-950/40">
                    <h3 class="text-base font-extrabold text-slate-100">{{ $supplierId ? 'Edit Supplier' : 'Register Supplier' }}</h3>
                    <button @click="$wire.closeForm()" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveSupplier" class="space-y-4">
                        <!-- Company Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Company Name *</label>
                            <input type="text" wire:model="name" required class="block w-full">
                        </div>
                        
                        <!-- Contact Person -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Contact Person Name</label>
                            <input type="text" wire:model="contactName" class="block w-full">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
                            <input type="email" wire:model="email" class="block w-full font-mono">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Phone / Tel</label>
                            <input type="text" wire:model="phone" class="block w-full font-mono">
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Billing / Office Address</label>
                            <textarea wire:model="address" rows="2" class="block w-full"></textarea>
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Default Payment Terms *</label>
                            <select wire:model="paymentTerms" required class="block w-full">
                                <option value="cash">Cash on Delivery</option>
                                <option value="net15">Net 15</option>
                                <option value="net30">Net 30</option>
                                <option value="net60">Net 60</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/80">
                            <button type="button" @click="$wire.closeForm()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-semibold text-xs transition duration-150">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition text-xs shadow-lg shadow-indigo-600/10">Save Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form Modal -->
    <div x-data="{ show: @entangle('isPayOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closePay()" class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-800/80 flex justify-between items-center bg-slate-950/40">
                    <h3 class="text-base font-extrabold text-slate-100">Record Payment</h3>
                    <button @click="$wire.closePay()" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <div class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 p-3.5 rounded-lg mb-4 text-xs font-semibold">
                        Paying Supplier: <strong class="text-indigo-200">{{ $paySupplierName }}</strong>
                    </div>

                    <form wire:submit.prevent="submitPayment" class="space-y-4">
                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Payment Amount ($) *</label>
                            <input type="number" wire:model="payAmount" required min="0.01" step="0.01" class="block w-full font-mono">
                        </div>

                        <!-- Method -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Payment Method *</label>
                            <select wire:model="payMethod" required class="block w-full">
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="bank_transfer">Bank Transfer / EFT</option>
                                <option value="cheque">Bank Cheque</option>
                                <option value="card">Credit/Debit Card</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Notes / Reference</label>
                            <textarea wire:model="payNotes" rows="2" placeholder="Cheque number, TXN reference..." class="block w-full"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/80">
                            <button type="button" @click="$wire.closePay()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-semibold text-xs transition duration-150">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold transition text-xs shadow-lg shadow-emerald-600/10">Post Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>