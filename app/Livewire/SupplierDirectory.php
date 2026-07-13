<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class SupplierDirectory extends Component
{
    public $search = '';
    public $statusFilter = 'all';

    // Supplier creation/edit form fields
    public $supplierId = null;
    public $name = '';
    public $contactName = ''; // expected by tests
    public $email = '';
    public $phone = '';
    public $address = '';
    public $paymentTerms = 'Net 30'; // expected by tests
    public $outstandingBalance = 0.00; // expected by tests
    public $status = true;

    // Payment form fields
    public $payAmount = '';
    public $payMethod = 'cash';
    public $payNotes = '';
    public $selectedSupplierForPayment = null;

    public $showCreateModal = false;
    public $showPaymentModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'contactName' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:500',
        'paymentTerms' => 'required|string|max:100',
        'outstandingBalance' => 'required|numeric|min:0',
        'status' => 'boolean',
    ];

    public function openCreate()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEdit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->contactName = $supplier->contact_name;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->paymentTerms = $supplier->payment_terms;
        $this->outstandingBalance = $supplier->outstanding_balance;
        $this->status = (bool) $supplier->status;

        $this->showCreateModal = true;
    }

    // Method expected by tests
    public function openPayModal($id)
    {
        $this->selectedSupplierForPayment = Supplier::findOrFail($id);
        $this->payAmount = $this->selectedSupplierForPayment->outstanding_balance;
        $this->payMethod = 'cash';
        $this->payNotes = '';
        $this->showPaymentModal = true;
    }

    public function saveSupplier()
    {
        $this->validate();

        if ($this->supplierId) {
            $supplier = Supplier::findOrFail($this->supplierId);
            $supplier->update([
                'name' => $this->name,
                'contact_name' => $this->contactName,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'payment_terms' => $this->paymentTerms,
                'outstanding_balance' => $this->outstandingBalance,
                'status' => $this->status,
            ]);
            session()->flash('message', 'Supplier updated successfully!');
        } else {
            Supplier::create([
                'name' => $this->name,
                'contact_name' => $this->contactName,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'payment_terms' => $this->paymentTerms,
                'outstanding_balance' => $this->outstandingBalance,
                'status' => $this->status,
            ]);
            session()->flash('message', 'Supplier added successfully!');
        }

        $this->resetForm();
        $this->showCreateModal = false;
    }

    // Method expected by tests
    public function submitPayment()
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:0.01',
            'payMethod' => 'required|string',
        ]);

        DB::transaction(function () {
            SupplierPayment::create([
                'supplier_id' => $this->selectedSupplierForPayment->id,
                'amount_paid' => $this->payAmount,
                'payment_method' => $this->payMethod,
                'notes' => $this->payNotes,
            ]);

            $this->selectedSupplierForPayment->decrement('outstanding_balance', $this->payAmount);
        });

        session()->flash('message', 'Payment recorded successfully! Outstanding balance reduced.');
        $this->showPaymentModal = false;
        $this->selectedSupplierForPayment = null;
    }

    public function toggleStatus($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['status' => !$supplier->status]);
        session()->flash('message', 'Supplier status toggled.');
    }

    private function resetForm()
    {
        $this->reset(['supplierId', 'name', 'contactName', 'email', 'phone', 'address', 'paymentTerms', 'outstandingBalance', 'status']);
        $this->status = true;
        $this->paymentTerms = 'Net 30';
    }

    public function render()
    {
        $query = Supplier::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter === 'active');
        }

        $suppliers = $query->orderBy('name', 'asc')->get();

        return view('livewire.supplier-directory', compact('suppliers'));
    }
}
