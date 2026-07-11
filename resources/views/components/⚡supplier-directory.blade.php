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
    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex flex-col md:flex-row gap-4 items-center justify-between mb-6">
        <div class="w-full md:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by company or email..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
        </div>
        <button wire:click="openCreate" class="w-full md:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-sm transition duration-150 text-xs">
            + Register Supplier
        </button>
    </div>

    <!-- Suppliers Grid/Table -->
    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                <tr>
                    <th class="px-6 py-3">Supplier Name</th>
                    <th class="px-6 py-3">Contact Person</th>
                    <th class="px-6 py-3">Phone & Email</th>
                    <th class="px-6 py-3">Payment Terms</th>
                    <th class="px-6 py-3">Outstanding Dues</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($this->getSuppliers() as $sup)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $sup->name }}</td>
                        <td class="px-6 py-4 text-gray-600 font-medium">{{ $sup->contact_name ?: '-' }}</td>
                        <td class="px-6 py-4 space-y-0.5 text-xs text-gray-500">
                            <div>{{ $sup->phone ?: '-' }}</div>
                            <div>{{ $sup->email ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded">
                                {{ strtoupper($sup->payment_terms) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">
                            @if($sup->outstanding_balance > 0)
                                <span class="text-rose-600">${{ number_format($sup->outstanding_balance, 2) }}</span>
                            @else
                                <span class="text-emerald-600">$0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2.5">
                            @if($sup->outstanding_balance > 0)
                                <button wire:click="openPayModal({{ $sup->id }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition">
                                    Pay Dues
                                </button>
                            @endif
                            <button wire:click="openEdit({{ $sup->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition">
                                Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-12 text-xs">No registered suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Supplier Form Modal -->
    <div x-data="{ show: @entangle('isFormOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closeForm()" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">{{ $supplierId ? 'Edit Supplier' : 'Register Supplier' }}</h3>
                    <button @click="$wire.closeForm()" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveSupplier" class="space-y-4">
                        <!-- Company Name -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Company Name *</label>
                            <input type="text" wire:model="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        
                        <!-- Contact Person -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Contact Person Name</label>
                            <input type="text" wire:model="contactName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Email Address</label>
                            <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Phone / Tel</label>
                            <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Billing / Office Address</label>
                            <textarea wire:model="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Default Payment Terms *</label>
                            <select wire:model="paymentTerms" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                <option value="cash">Cash on Delivery</option>
                                <option value="net15">Net 15</option>
                                <option value="net30">Net 30</option>
                                <option value="net60">Net 60</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.closeForm()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-750 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">Save Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form Modal -->
    <div x-data="{ show: @entangle('isPayOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.closePay()" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Record Payment</h3>
                    <button @click="$wire.closePay()" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <div class="bg-indigo-50 text-indigo-800 p-3 rounded mb-4 text-xs font-semibold">
                        Paying Supplier: <strong class="text-indigo-950">{{ $paySupplierName }}</strong>
                    </div>

                    <form wire:submit.prevent="submitPayment" class="space-y-4">
                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Payment Amount ($) *</label>
                            <input type="number" wire:model="payAmount" required min="0.01" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Method -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Payment Method *</label>
                            <select wire:model="payMethod" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="bank_transfer">Bank Transfer / EFT</option>
                                <option value="cheque">Bank Cheque</option>
                                <option value="card">Credit/Debit Card</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Notes / Reference</label>
                            <textarea wire:model="payNotes" rows="2" placeholder="Cheque number, TXN reference..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.closePay()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-750 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold transition text-xs">Post Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>