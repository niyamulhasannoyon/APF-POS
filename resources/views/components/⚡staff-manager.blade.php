<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Branch;
use App\Models\StaffShift;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $activeTab = 'staff'; // staff, shifts, audits

    // Staff Form Fields
    public $staffId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'cashier';
    public $branchId = '';
    public $commissionRate = 0.00;
    public $isStaffOpen = false;

    // Shift Form Fields
    public $startCash = '';
    public $endCash = '';
    public $shiftNotes = '';
    public $isShiftOpenModal = false;
    public $isShiftCloseModal = false;

    public $branches = [];
    public $usersList = [];
    public $shiftsList = [];
    public $auditsList = [];

    public function mount()
    {
        $this->branches = Branch::all();
        if (count($this->branches) > 0) {
            $this->branchId = $this->branches[0]->id;
        }
        $this->loadData();
    }

    public function loadData()
    {
        $this->usersList = User::with('branch')->orderBy('name', 'asc')->get();
        $this->shiftsList = StaffShift::with(['user', 'branch'])->orderBy('created_at', 'desc')->get();
        $this->auditsList = ActivityLog::with('user')->orderBy('created_at', 'desc')->take(100)->get();
    }

    public function openStaffCreate()
    {
        $this->resetStaffForm();
        $this->isStaffOpen = true;
    }

    public function openStaffEdit($id)
    {
        $this->resetStaffForm();
        $user = User::findOrFail($id);
        $this->staffId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?: 'cashier';
        $this->branchId = $user->branch_id ?: '';
        $this->commissionRate = $user->commission_rate;

        $this->isStaffOpen = true;
    }

    public function saveStaff()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->staffId,
            'password' => $this->staffId ? 'nullable|min:6' : 'required|min:6',
            'role' => 'required|string|in:admin,manager,cashier,accountant',
            'branchId' => 'required|exists:branches,id',
            'commissionRate' => 'required|numeric|min:0|max:100'
        ]);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'branch_id' => $this->branchId,
            'commission_rate' => $this->commissionRate
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->staffId], $userData);

        $this->isStaffOpen = false;
        $this->loadData();
        
        // Log action
        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $this->staffId ? 'staff_edit' : 'staff_create',
            'description' => "Saved staff profile: {$this->name} with role: {$this->role}"
        ]);

        session()->flash('success', 'Staff details saved successfully.');
    }

    public function openStartShift()
    {
        $this->startCash = '';
        $this->shiftNotes = '';
        $this->isShiftOpenModal = true;
    }

    public function startShiftSubmit()
    {
        $this->validate([
            'startCash' => 'required|numeric|min:0',
            'shiftNotes' => 'nullable|string|max:500'
        ]);

        $activeShift = StaffShift::where('user_id', auth()->id() ?? 1)->where('status', 'open')->first();
        if ($activeShift) {
            session()->flash('error', 'You already have an open shift.');
            return;
        }

        StaffShift::create([
            'user_id' => auth()->id() ?? 1,
            'branch_id' => auth()->user()->branch_id ?? $this->branchId ?: 1,
            'start_cash_balance' => $this->startCash,
            'notes' => $this->shiftNotes,
            'status' => 'open'
        ]);

        $this->isShiftOpenModal = false;
        $this->loadData();

        // Log action
        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => 'shift_start',
            'description' => "Opened a new register shift with start cash: \${$this->startCash}"
        ]);

        session()->flash('success', 'Work shift started. Register opened.');
    }

    public function openCloseShift($shiftId)
    {
        $this->staffId = $shiftId; // reusing variable for active shift id
        $this->endCash = '';
        $this->shiftNotes = '';
        $this->isShiftCloseModal = true;
    }

    public function closeShiftSubmit()
    {
        $this->validate([
            'endCash' => 'required|numeric|min:0',
            'shiftNotes' => 'nullable|string|max:500'
        ]);

        $shift = StaffShift::findOrFail($this->staffId);
        $shift->update([
            'end_cash_balance' => $this->endCash,
            'end_time' => now(),
            'status' => 'closed',
            'notes' => $shift->notes . " | Closing notes: " . $this->shiftNotes
        ]);

        $this->isShiftCloseModal = false;
        $this->loadData();

        // Log action
        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => 'shift_end',
            'description' => "Closed work shift. Ending cash registered: \${$this->endCash}"
        ]);

        session()->flash('success', 'Work shift ended. Register closed.');
    }

    public function resetStaffForm()
    {
        $this->staffId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'cashier';
        $this->commissionRate = 0.00;
    }
};
?>

<div>
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-6 flex gap-4">
        <button wire:click="$set('activeTab', 'staff')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'staff' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            🧑💼 Staff Directory
        </button>
        <button wire:click="$set('activeTab', 'shifts')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'shifts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            ⏰ Shifts & Attendance
        </button>
        <button wire:click="$set('activeTab', 'audits')" class="py-2.5 px-4 font-semibold text-xs transition border-b-2" :class="'{{ $activeTab }}' === 'audits' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            📋 Audit Log
        </button>
    </div>

    @if($activeTab === 'staff')
        <!-- Action Bar -->
        <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex justify-between items-center mb-6">
            <h4 class="text-sm font-bold text-gray-700 font-sans">Staff Registry</h4>
            <button wire:click="openStaffCreate" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">
                + Add Staff Member
            </button>
        </div>

        <!-- Staff List Table -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Assigned Branch</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Commission Rate</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($usersList as $usr)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ $usr->name }}</td>
                            <td class="px-6 py-4 text-gray-650">{{ $usr->email }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $usr->branch->name ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded text-[9px]" :class="{
                                    'bg-indigo-100 text-indigo-800': '{{ $usr->role }}' === 'admin',
                                    'bg-blue-100 text-blue-800': '{{ $usr->role }}' === 'manager',
                                    'bg-emerald-100 text-emerald-800': '{{ $usr->role }}' === 'cashier',
                                    'bg-amber-100 text-amber-800': '{{ $usr->role }}' === 'accountant'
                                }">
                                    {{ $usr->role ?: 'cashier' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-indigo-900">{{ $usr->commission_rate }}%</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openStaffEdit({{ $usr->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition">
                                    Edit Staff
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif($activeTab === 'shifts')
        <!-- Shifts Attendance action bar -->
        <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex justify-between items-center mb-6">
            <h4 class="text-sm font-bold text-gray-700">Cash Register Shifts</h4>
            <button wire:click="openStartShift" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold transition text-xs">
                ⏰ Open Cashier Shift
            </button>
        </div>

        <!-- Shift list table -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Cashier</th>
                        <th class="px-6 py-3">Branch</th>
                        <th class="px-6 py-3">Start Cash</th>
                        <th class="px-6 py-3">End Cash</th>
                        <th class="px-6 py-3">Shift Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($shiftsList as $shift)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $shift->user->name }}</div>
                                <div class="text-[10px] text-gray-500">{{ $shift->start_time->format('M d, H:i') }} @if($shift->end_time) - {{ $shift->end_time->format('H:i') }} @endif</div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ $shift->branch->name }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-800">${{ number_format($shift->start_cash_balance, 2) }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                {{ $shift->end_cash_balance !== null ? '$' . number_format($shift->end_cash_balance, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 select-none">
                                <span class="text-xs px-2.5 py-1 rounded-full font-bold uppercase text-[9px]" :class="{
                                    'bg-emerald-100 text-emerald-800': '{{ $shift->status }}' === 'open',
                                    'bg-gray-200 text-gray-600': '{{ $shift->status }}' === 'closed'
                                }">
                                    {{ $shift->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($shift->status === 'open' && $shift->user_id === auth()->id())
                                    <button wire:click="openCloseShift({{ $shift->id }})" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-bold transition shadow-sm">
                                        Close Shift
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-12 text-xs">No work shifts recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Audit log display -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-600 uppercase">
                Staff Audit Logs (Last 100 entries)
            </div>
            <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto pr-1">
                @forelse($auditsList as $audit)
                    <div class="p-4 flex justify-between items-center text-xs hover:bg-gray-50/50">
                        <div class="space-y-1">
                            <div class="text-gray-800 font-medium">
                                <strong class="text-gray-950 font-bold">{{ $audit->user->name ?? 'System' }}</strong>: {{ $audit->description }}
                            </div>
                            <div class="text-[10px] text-gray-500">
                                IP: {{ $audit->ip_address ?: 'Internal' }} • Date: {{ $audit->created_at->format('M d, Y h:ia') }}
                            </div>
                        </div>
                        <span class="font-mono text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded uppercase font-semibold">
                            {{ $audit->action }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-12 text-xs">No audit logs logged in database.</div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Staff Edit Modal -->
    <div x-data="{ show: @entangle('isStaffOpen') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isStaffOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">{{ $staffId ? 'Edit Staff Profile' : 'Add Staff Member' }}</h3>
                    <button @click="$wire.isStaffOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveStaff" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Full Name *</label>
                            <input type="text" wire:model="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Email Address *</label>
                            <input type="email" wire:model="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Password {{ $staffId ? '(Leave blank to keep current)' : '*' }}</label>
                            <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Role / Access Level *</label>
                            <select wire:model="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                <option value="admin">Administrator</option>
                                <option value="manager">Manager</option>
                                <option value="cashier">Cashier</option>
                                <option value="accountant">Accountant</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Assigned Branch *</label>
                            <select wire:model="branchId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Sales Commission Rate (%) *</label>
                            <input type="number" wire:model="commissionRate" required min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isStaffOpen = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs">Save Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Shift Modal -->
    <div x-data="{ show: @entangle('isShiftOpenModal') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isShiftOpenModal = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Open Register / Start Shift</h3>
                    <button @click="$wire.isShiftOpenModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="startShiftSubmit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Starting Cash Drawer Balance ($) *</label>
                            <input type="number" wire:model="startCash" required min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Starting Notes</label>
                            <textarea wire:model="shiftNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm" placeholder="Drawer notes..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isShiftOpenModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold transition text-xs">Open Drawer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Close Shift Modal -->
    <div x-data="{ show: @entangle('isShiftCloseModal') }">
        <div x-show="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div @click.away="$wire.isShiftCloseModal = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-[scaleUp_0.15s]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Close Register / End Shift</h3>
                    <button @click="$wire.isShiftCloseModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="closeShiftSubmit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Ending Cash Drawer Balance ($) *</label>
                            <input type="number" wire:model="endCash" required min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Closing Notes</label>
                            <textarea wire:model="shiftNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm" placeholder="Drawer discrepancies..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="$wire.isShiftCloseModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-755 rounded font-semibold text-xs transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold transition text-xs">Close Drawer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>