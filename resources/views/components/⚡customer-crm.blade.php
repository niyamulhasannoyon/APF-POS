<?php

use Livewire\Component;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // Active Tab
    public $activeTab = 'customers'; // customers, groups

    // Customer Group Form Fields
    public $groupId = null;
    public $groupName = '';
    public $discountPercentage = 0.00;
    public $isGroupOpen = false;

    // Customer Profile Details & Form Fields
    public $customerId = null;
    public $name = '';
    public $phone = '';
    public $email = '';
    public $customerGroupId = '';
    public $loyaltyPoints = 0;
    public $isCustomerOpen = false;

    // Financial Adjustments Form Fields
    public $adjCustomerId = null;
    public $adjCustomerName = '';
    public $adjType = 'credit_added'; // credit_added, credit_used, due_added, due_paid
    public $adjAmount = '';
    public $adjNotes = '';
    public $isAdjustmentOpen = false;

    public $search = '';

    public function getCustomers()
    {
        return Customer::with('group')
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('phone', 'like', "%{$this->search}%")
            ->orderBy('name', 'asc')
            ->get();
    }

    public function getGroups()
    {
        return CustomerGroup::orderBy('name', 'asc')->get();
    }

    public function openGroupCreate()
    {
        $this->groupId = null;
        $this->groupName = '';
        $this->discountPercentage = 0.00;
        $this->isGroupOpen = true;
    }

    public function openGroupEdit($id)
    {
        $group = CustomerGroup::findOrFail($id);
        $this->groupId = $group->id;
        $this->groupName = $group->name;
        $this->discountPercentage = $group->discount_percentage;
        $this->isGroupOpen = true;
    }

    public function saveGroup()
    {
        $this->validate([
            'groupName' => 'required|string|max:255',
            'discountPercentage' => 'required|numeric|min:0|max:100'
        ]);

        CustomerGroup::updateOrCreate(
            ['id' => $this->groupId],
            [
                'name' => $this->groupName,
                'discount_percentage' => $this->discountPercentage
            ]
        );

        $this->isGroupOpen = false;
        session()->flash('success', 'Customer tier saved successfully.');
    }

    public function openCustomerEdit($id)
    {
        $customer = Customer::findOrFail($id);
        $this->customerId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->customerGroupId = $customer->customer_group_id ?: '';
        $this->loyaltyPoints = $customer->loyalty_points;
        $this->isCustomerOpen = true;
    }

    public function saveCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'customerGroupId' => 'nullable|exists:customer_groups,id',
            'loyaltyPoints' => 'required|integer|min:0'
        ]);

        $customer = Customer::findOrFail($this->customerId);
        $customer->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'customer_group_id' => $this->customerGroupId ?: null,
            'loyalty_points' => $this->loyaltyPoints
        ]);

        $this->isCustomerOpen = false;
        session()->flash('success', 'Customer profile updated successfully.');
    }

    public function openAdjustment($id)
    {
        $customer = Customer::findOrFail($id);
        $this->adjCustomerId = $customer->id;
        $this->adjCustomerName = $customer->name;
        $this->adjType = 'credit_added';
        $this->adjAmount = '';
        $this->adjNotes = '';
        $this->isAdjustmentOpen = true;
    }

    public function submitAdjustment()
    {
        $customer = Customer::findOrFail($this->adjCustomerId);

        $this->validate([
            'adjType' => 'required|in:credit_added,credit_used,due_added,due_paid',
            'adjAmount' => 'required|numeric|min:0.01',
            'adjNotes' => 'nullable|string|max:500'
        ]);

        // Specific limits check
        if ($this->adjType === 'credit_used' && $customer->store_credit < $this->adjAmount) {
            $this->addError('adjAmount', 'Customer has insufficient store credit.');
            return;
        }
        if ($this->adjType === 'due_paid' && $customer->outstanding_dues < $this->adjAmount) {
            $this->addError('adjAmount', 'Payment exceeds outstanding dues balance.');
            return;
        }

        DB::transaction(function () use ($customer) {
            CustomerTransaction::create([
                'customer_id' => $customer->id,
                'type' => $this->adjType,
                'amount' => $this->adjAmount,
                'notes' => $this->adjNotes
            ]);

            if ($this->adjType === 'credit_added') {
                $customer->increment('store_credit', $this->adjAmount);
            } elseif ($this->adjType === 'credit_used') {
                $customer->decrement('store_credit', $this->adjAmount);
            } elseif ($this->adjType === 'due_added') {
                $customer->increment('outstanding_dues', $this->adjAmount);
            } elseif ($this->adjType === 'due_paid') {
                $customer->decrement('outstanding_dues', $this->adjAmount);
            }
        });

        $this->isAdjustmentOpen = false;
        session()->flash('success', 'Customer balance adjustment recorded.');
    }
};
?>

<div>
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-6 flex gap-4">
        <button wire:click="$set('activeTab', 'customers')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'customers' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            👥 Customers Ledger
        </button>
        <button wire:click="$set('activeTab', 'groups')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'groups' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            💎 Customer Tiers & Pricing
        </button>
    </div>

    @if($activeTab === 'customers')
        <!-- Customer Tab action bar -->
        <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex flex-col md:flex-row gap-4 items-center justify-between mb-6">
            <div class="w-full md:w-1/3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or phone..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
            </div>
        </div>

        <!-- Customers List Table -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Client Details</th>
                        <th class="px-6 py-3">Pricing Tier</th>
                        <th class="px-6 py-3">Loyalty Points</th>
                        <th class="px-6 py-3">Store Credit</th>
                        <th class="px-6 py-3">Outstanding Dues</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($this->getCustomers() as $cust)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $cust->name }}</div>
                                <div class="text-xs text-gray-500 font-medium">{{ $cust->phone ?: '-' }} • {{ $cust->email ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded font-bold">
                                    {{ $cust->group->name ?? 'Retail Pricing' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-indigo-900">{{ $cust->loyalty_points }} pts</td>
                            <td class="px-6 py-4 font-bold text-emerald-600">${{ number_format($cust->store_credit, 2) }}</td>
                            <td class="px-6 py-4 font-bold text-rose-600">${{ number_format($cust->outstanding_dues, 2) }}</td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <button wire:click="openAdjustment({{ $cust->id }})" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-white rounded text-xs font-bold transition">
                                    Adjust Balance
                                </button>
                                <button wire:click="openCustomerEdit({{ $cust->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition">
                                    Edit Profile
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-12 text-xs">No customer profiles registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Pricing groups tab action bar -->
        <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex justify-between items-center mb-6">
            <h4 class="text-sm font-bold text-gray-700">Client Tiers</h4>
            <button wire:click="openGroupCreate" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">
                + Create Pricing Tier
            </button>
        </div>

        <!-- Groups Table -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Tier Name</th>
                        <th class="px-6 py-3">Default Discount</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($this->getGroups() as $grp)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ $grp->name }}</td>
                            <td class="px-6 py-4 text-gray-700 font-bold">{{ $grp->discount_percentage }}% Discount</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openGroupEdit({{ $grp->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition">
                                    Edit Tier
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-400 py-12 text-xs">No client pricing tiers configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Pricing Group Modal -->
    <div x-data="{ show: @entangle('isGroupOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isGroupOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">{{ $groupId ? 'Edit Tier' : 'Create Pricing Tier' }}</h3>
                    <button @click="$wire.isGroupOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveGroup" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Tier Name *</label>
                            <input type="text" wire:model="groupName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Discount Percentage (%) *</label>
                            <input type="number" wire:model="discountPercentage" required min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isGroupOpen = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">Save Tier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Edit Modal -->
    <div x-data="{ show: @entangle('isCustomerOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isCustomerOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Edit Customer Profile</h3>
                    <button @click="$wire.isCustomerOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveCustomer" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Customer Name *</label>
                            <input type="text" wire:model="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Phone</label>
                            <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Email</label>
                            <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Pricing Group</label>
                            <select wire:model="customerGroupId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                <option value="">Retail / None</option>
                                @foreach($this->getGroups() as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->discount_percentage }}% Off)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Loyalty Points Balance *</label>
                            <input type="number" wire:model="loyaltyPoints" required min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isCustomerOpen = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Credit/Dues Adjustment Modal -->
    <div x-data="{ show: @entangle('isAdjustmentOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isAdjustmentOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Adjust Customer Ledger</h3>
                    <button @click="$wire.isAdjustmentOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <div class="bg-slate-50 text-gray-800 p-3 rounded mb-4 text-xs font-semibold">
                        Customer: <strong class="text-slate-950">{{ $adjCustomerName }}</strong>
                    </div>

                    <form wire:submit.prevent="submitAdjustment" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Adjustment Type *</label>
                            <select wire:model="adjType" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                <option value="credit_added">Add Store Credit (+)</option>
                                <option value="credit_used">Deduct Store Credit (-)</option>
                                <option value="due_added">Log Unpaid Due Balance (+)</option>
                                <option value="due_paid">Dues Payment Received (-)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Amount ($) *</label>
                            <input type="number" wire:model="adjAmount" required min="0.01" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Notes / Reason</label>
                            <textarea wire:model="adjNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm" placeholder="Reason details..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isAdjustmentOpen = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">Save Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>