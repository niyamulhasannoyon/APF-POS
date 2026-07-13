<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\StaffShift;
use App\Models\SalesCommission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class StaffManager extends Component
{
    // Staff details update
    public $editingStaffId = null;
    public $commission_rate = 0.00;

    // Shift clocking fields
    public $clockingUserId = '';
    public $clockingBranchId = '';
    
    // Properties expected by the tests
    public $startCash = 100.00;
    public $endCash = 0.00;
    public $shiftNotes = '';

    public $showShiftModal = false;
    public $selectedActiveShift = null;

    protected $rules = [
        'commission_rate' => 'required|numeric|min:0|max:100',
    ];

    public function editCommissionRate($id)
    {
        $staff = User::findOrFail($id);
        $this->editingStaffId = $staff->id;
        $this->commission_rate = $staff->commission_rate ?? 0.00;
    }

    public function saveCommissionRate()
    {
        $this->validate();
        
        $staff = User::findOrFail($this->editingStaffId);
        $staff->update(['commission_rate' => $this->commission_rate]);
        
        $this->editingStaffId = null;
        session()->flash('message', 'Commission rate updated successfully!');
    }

    public function openClockIn()
    {
        $this->clockingUserId = auth()->id();
        $this->clockingBranchId = auth()->user()->branch_id ?? \App\Models\Branch::first()->id ?? '';
        $this->startCash = 100.00;
        $this->shiftNotes = '';
        $this->showShiftModal = true;
    }

    // Method expected by the tests
    public function startShiftSubmit()
    {
        $userId = $this->clockingUserId ?: auth()->id();
        $branchId = $this->clockingBranchId ?: (auth()->user()->branch_id ?? \App\Models\Branch::first()->id ?? 1);

        DB::transaction(function () use ($userId, $branchId) {
            StaffShift::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'start_time' => now(),
                'start_cash_balance' => $this->startCash,
                'status' => 'open',
                'notes' => $this->shiftNotes,
            ]);

            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'shift_start',
                'notes' => 'Shift started with cash ' . $this->startCash,
            ]);
        });

        $this->showShiftModal = false;
        session()->flash('message', 'Shift clocked-in successfully! Cash drawer opened.');
    }

    // Method expected by the tests
    public function openCloseShift($shiftId)
    {
        $this->selectedActiveShift = StaffShift::findOrFail($shiftId);
        $this->endCash = $this->selectedActiveShift->start_cash_balance;
        $this->shiftNotes = $this->selectedActiveShift->notes ?? '';
    }

    // Method expected by the tests
    public function closeShiftSubmit()
    {
        if (!$this->selectedActiveShift) {
            $this->selectedActiveShift = StaffShift::where('user_id', auth()->id())->where('status', 'open')->first();
        }

        if (!$this->selectedActiveShift) {
            return;
        }

        DB::transaction(function () {
            $this->selectedActiveShift->update([
                'end_time' => now(),
                'end_cash_balance' => $this->endCash,
                'status' => 'closed',
                'notes' => $this->shiftNotes,
            ]);

            ActivityLog::create([
                'user_id' => $this->selectedActiveShift->user_id,
                'action' => 'shift_end',
                'notes' => 'Shift ended with cash ' . $this->endCash,
            ]);
        });

        $this->selectedActiveShift = null;
        session()->flash('message', 'Shift clocked-out successfully! Cash drawer closed.');
    }

    public function payCommission($id)
    {
        $commission = SalesCommission::findOrFail($id);
        $commission->update(['status' => 'paid']);
        session()->flash('message', 'Commission paid successfully!');
    }

    public function render()
    {
        $staffMembers = User::whereIn('role', ['cashier', 'manager'])->orderBy('name', 'asc')->get();
        $shifts = StaffShift::with(['user', 'branch'])->orderBy('start_time', 'desc')->get();
        $commissions = SalesCommission::with(['user', 'order'])->orderBy('created_at', 'desc')->get();
        $branches = \App\Models\Branch::all();

        return view('livewire.staff-manager', compact('staffMembers', 'shifts', 'commissions', 'branches'));
    }
}
