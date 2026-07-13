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
    <div class="border-b border-slate-800 mb-6 flex gap-4">
        <button wire:click="$set('activeTab', 'customers')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'customers' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
            👥 Customers Ledger
        </button>
        <button wire:click="$set('activeTab', 'groups')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'groups' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
            💎 Customer Tiers & Pricing
        </button>
    </div>

    @if($activeTab === 'customers')
        <!-- Customer Tab action bar -->
        <div class="glass-card flex flex-col md:flex-row gap-4 items-center justify-between mb-6 p-4.5">
            <div class="w-full md:w-1/3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or phone..." class="block w-full">
            </div>
        </div>

        <!-- Customers List Table -->
        <div class="glass-card p-0 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-900/40">
                        <th class="px-6 py-3.5">Client Details</th>
                        <th class="px-6 py-3.5">Pricing Tier</th>
                        <th class="px-6 py-3.5">Loyalty Points</th>
                        <th class="px-6 py-3.5">Store Credit</th>
                        <th class="px-6 py-3.5">Outstanding Dues</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->getCustomers() as $cust)
                        <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-200 text-sm">{{ $cust->name }}</div>
                                <div class="text-xs text-slate-500 mt-1 font-semibold">{{ $cust->phone ?: '-' }} • <span class="font-mono">{{ $cust->email ?: '-' }}</span></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs bg-indigo-500/10 text-indigo-300 px-2.5 py-1 rounded-md font-bold border border-indigo-500/20">
                                    {{ $cust->group->name ?? 'Retail Pricing' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-indigo-400 font-mono">{{ $cust->loyalty_points }} pts</td>
                            <td class="px-6 py-4 font-bold text-emerald-400 font-mono">${{ number_format($cust->store_credit, 2) }}</td>
                            <td class="px-6 py-4 font-bold text-rose-450 font-mono">${{ number_format($cust->outstanding_dues, 2) }}</td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <button wire:click="openAdjustment({{ $cust->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-bold transition duration-150">
                                    Adjust Balance
                                </button>
                                <button wire:click="openCustomerEdit({{ $cust->id }})" class="px-3 py-1.5 bg-slate-800/40 hover:bg-slate-700 text-slate-350 hover:text-slate-100 border border-slate-700/60 rounded-lg text-xs font-semibold transition duration-150">
                                    Edit Profile
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-12 text-xs">No customer profiles registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Pricing groups tab action bar -->
        <div class="glass-card flex justify-between items-center mb-6 p-4.5">
            <h4 class="text-sm font-bold text-slate-200">Client Tiers</h4>
            <button wire:click="openGroupCreate" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 text-xs">
                + Create Pricing Tier
            </button>
        </div>

        <!-- Groups Table -->
        <div class="glass-card p-0 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-900/40">
                        <th class="px-6 py-3.5">Tier Name</th>
                        <th class="px-6 py-3.5">Default Discount</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->getGroups() as $grp)
                        <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4 font-bold text-slate-200">{{ $grp->name }}</td>
                            <td class="px-6 py-4 text-emerald-400 font-bold font-mono">{{ $grp->discount_percentage }}% Discount</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openGroupEdit({{ $grp->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-semibold transition duration-150">
                                    Edit Tier
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-slate-500 py-12 text-xs">No client pricing tiers configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Pricing Group Modal -->
    <div x-data=" { show: @entangle('isGroupOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isGroupOpen = false" class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-800/80 flex justify-between items-center bg-slate-950/40">
                    <h3 class="text-base font-extrabold text-slate-100">{{ $groupId ? 'Edit Tier' : 'Create Pricing Tier' }}</h3>
                    <button @click="$wire.isGroupOpen = false" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveGroup" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tier Name *</label>
                            <input type="text" wire:model="groupName" required class="block w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Discount Percentage (%) *</label>
                            <input type="number" wire:model="discountPercentage" required min="0" max="100" step="0.01" class="block w-full font-mono">
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/80">
                            <button type="button" @click="$wire.isGroupOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-semibold text-xs transition duration-150">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition text-xs shadow-lg shadow-indigo-600/10">Save Tier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Edit Modal -->
    <div x-data="{ show: @entangle('isCustomerOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isCustomerOpen = false" class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-800/80 flex justify-between items-center bg-slate-950/40">
                    <h3 class="text-base font-extrabold text-slate-100">Edit Customer Profile</h3>
                    <button @click="$wire.isCustomerOpen = false" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveCustomer" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Customer Name *</label>
                            <input type="text" wire:model="name" required class="block w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Phone</label>
                            <input type="text" wire:model="phone" class="block w-full font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" wire:model="email" class="block w-full font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pricing Group</label>
                            <select wire:model="customerGroupId" class="block w-full">
                                <option value="">Retail / None</option>
                                @foreach($this->getGroups() as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->discount_percentage }}% Off)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Loyalty Points Balance *</label>
                            <input type="number" wire:model="loyaltyPoints" required min="0" class="block w-full font-mono">
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/80">
                            <button type="button" @click="$wire.isCustomerOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-semibold text-xs transition duration-150">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition text-xs shadow-lg shadow-indigo-600/10">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Credit/Dues Adjustment Modal -->
    <div x-data="{ show: @entangle('isAdjustmentOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isAdjustmentOpen = false" class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-800/80 flex justify-between items-center bg-slate-950/40">
                    <h3 class="text-base font-extrabold text-slate-100">Adjust Customer Ledger</h3>
                    <button @click="$wire.isAdjustmentOpen = false" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <div class="bg-slate-950/60 text-slate-300 border border-slate-800/85 p-3 rounded-lg mb-4 text-xs font-semibold">
                        Customer: <strong class="text-indigo-400">{{ $adjCustomerName }}</strong>
                    </div>

                    <form wire:submit.prevent="submitAdjustment" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Adjustment Type *</label>
                            <select wire:model="adjType" required class="block w-full">
                                <option value="credit_added">Add Store Credit (+)</option>
                                <option value="credit_used">Deduct Store Credit (-)</option>
                                <option value="due_added">Log Unpaid Due Balance (+)</option>
                                <option value="due_paid">Dues Payment Received (-)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Amount ($) *</label>
                            <input type="number" wire:model="adjAmount" required min="0.01" step="0.01" class="block w-full font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Notes / Reason</label>
                            <textarea wire:model="adjNotes" rows="2" class="block w-full" placeholder="Reason details..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/80">
                            <button type="button" @click="$wire.isAdjustmentOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-semibold text-xs transition duration-150">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition text-xs shadow-lg shadow-indigo-600/10">Save Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>