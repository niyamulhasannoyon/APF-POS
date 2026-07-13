<div class="space-y-8">
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Section 1: Staff Directory & Commission Rates -->
    <div class="glass-card">
        <h3 class="text-base font-bold text-slate-100 mb-4 flex items-center gap-2">👤 Staff Commission Rates</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-2.5 px-4">Staff Name</th>
                        <th class="py-2.5 px-4">Email</th>
                        <th class="py-2.5 px-4">Role</th>
                        <th class="py-2.5 px-4 text-right">Commission Rate (%)</th>
                        <th class="py-2.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach ($staffMembers as $staff)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3 px-4 text-sm font-semibold text-slate-200">{{ $staff->name }}</td>
                            <td class="py-3 px-4 text-xs text-slate-400">{{ $staff->email }}</td>
                            <td class="py-3 px-4 text-xs">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $staff->role === 'manager' ? 'bg-indigo-500/10 text-indigo-300' : 'bg-slate-700 text-slate-300' }}">
                                    {{ $staff->role }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-right font-mono text-slate-100">
                                @if ($editingStaffId === $staff->id)
                                    <input type="number" wire:model.defer="commission_rate" class="w-20 text-xs text-right" step="0.1" min="0" max="100">
                                @else
                                    {{ number_format($staff->commission_rate, 2) }}%
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                @if ($editingStaffId === $staff->id)
                                    <button wire:click="saveCommissionRate" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-bold transition">
                                        Save
                                    </button>
                                    <button wire:click="$set('editingStaffId', null)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs font-bold transition">
                                        Cancel
                                    </button>
                                @else
                                    <button wire:click="editCommissionRate({{ $staff->id }})" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded text-xs font-bold transition">
                                        Edit Rate
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Cashier Shifts Registry -->
    <div class="glass-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">⏰ Staff Shifts Register</h3>
            @php
                $activeShift = \App\Models\StaffShift::where('user_id', auth()->id())->where('status', 'open')->first();
            @endphp
            @if ($activeShift)
                <button wire:click="openCloseShift({{ $activeShift->id }})" class="btn-rose">
                    🛑 Clock Out Active Shift
                </button>
            @else
                <button wire:click="openClockIn" class="btn-indigo">
                    🟢 Clock In Shift
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-2.5 px-4">Staff Member</th>
                        <th class="py-2.5 px-4">Branch</th>
                        <th class="py-2.5 px-4">Clocked In</th>
                        <th class="py-2.5 px-4">Clocked Out</th>
                        <th class="py-2.5 px-4 text-right">Start Cash</th>
                        <th class="py-2.5 px-4 text-right">End Cash</th>
                        <th class="py-2.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse ($shifts as $shift)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3 px-4 text-sm font-semibold text-slate-200">{{ $shift->user->name }}</td>
                            <td class="py-3 px-4 text-sm text-slate-350">{{ $shift->branch->name }}</td>
                            <td class="py-3 px-4 text-xs font-mono text-slate-400">{{ $shift->start_time->format('M d, g:i A') }}</td>
                            <td class="py-3 px-4 text-xs font-mono text-slate-400">
                                {{ $shift->end_time ? $shift->end_time->format('M d, g:i A') : '--' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-right font-mono text-slate-200">${{ number_format($shift->start_cash_balance, 2) }}</td>
                            <td class="py-3 px-4 text-sm text-right font-mono text-slate-100">
                                {{ $shift->end_cash_balance !== null ? '$' . number_format($shift->end_cash_balance, 2) : 'Open Drawer' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if ($shift->status === 'open')
                                    <span class="badge-emerald">Active</span>
                                @else
                                    <span class="badge-primary">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-500">No shifts recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Cashier Commissions Settle -->
    <div class="glass-card">
        <h3 class="text-base font-bold text-slate-100 mb-4 flex items-center gap-2">💵 Sales Commissions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-2.5 px-4">Date</th>
                        <th class="py-2.5 px-4">Cashier</th>
                        <th class="py-2.5 px-4">Order UUID</th>
                        <th class="py-2.5 px-4 text-right">Commission Amount</th>
                        <th class="py-2.5 px-4 text-center">Status</th>
                        <th class="py-2.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse ($commissions as $commission)
                        <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                            <td class="py-3 px-4 text-xs font-mono text-slate-400">{{ $commission->created_at->format('M d, Y h:i A') }}</td>
                            <td class="py-3 px-4 text-sm font-semibold text-slate-200">{{ $commission->user->name }}</td>
                            <td class="py-3 px-4 text-xs font-mono text-indigo-400">{{ $commission->order->offline_id }}</td>
                            <td class="py-3 px-4 text-sm font-bold text-slate-100 text-right font-mono">${{ number_format($commission->commission_amount, 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                @if ($commission->status === 'paid')
                                    <span class="badge-emerald">Setted</span>
                                @else
                                    <span class="badge-amber">Unpaid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($commission->status === 'pending')
                                    <button wire:click="payCommission({{ $commission->id }})" class="px-2 py-1 bg-emerald-600/10 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/20 rounded text-[11px] font-semibold transition">
                                        Mark Paid 💰
                                    </button>
                                @else
                                    <span class="text-xs text-slate-500">Paid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-slate-500">No commissions earned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clock In Modal -->
    @if ($showShiftModal)
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[380px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">Clock In Shift</h3>
                    <button wire:click="$set('showShiftModal', false)" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                <form wire:submit.prevent="startShiftSubmit" class="p-5 space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Opening Drawer Cash ($)</label>
                        <input type="number" wire:model.defer="startCash" class="w-full text-right font-mono" step="0.01" min="0">
                        @error('startCash') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Shift Notes</label>
                        <input type="text" wire:model.defer="shiftNotes" class="w-full" placeholder="Drawer #1, Morning shift...">
                    </div>

                    <div class="pt-4 border-t border-slate-700 flex gap-3">
                        <button type="submit" class="btn-indigo flex-1 font-bold">Start Shift</button>
                        <button type="button" wire:click="$set('showShiftModal', false)" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Clock Out Drawer Modal -->
    @if ($selectedActiveShift)
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[380px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">Clock Out Shift</h3>
                    <button wire:click="$set('selectedActiveShift', null)" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                <form wire:submit.prevent="closeShiftSubmit" class="p-5 space-y-4">
                    <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Started At</p>
                        <p class="text-xs text-slate-350 font-mono">{{ $selectedActiveShift->start_time->format('M d, g:i A') }}</p>
                        <p class="text-[10px] font-bold text-slate-500 uppercase mt-2">Opening Cash</p>
                        <p class="text-xs text-slate-350 font-mono">${{ number_format($selectedActiveShift->start_cash_balance, 2) }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Closing Drawer Cash ($)</label>
                        <input type="number" wire:model.defer="endCash" class="w-full text-right font-mono" step="0.01" min="0">
                        @error('endCash') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-400">Shift Notes / Discrepancy</label>
                        <input type="text" wire:model.defer="shiftNotes" class="w-full" placeholder="Reason for cash difference, if any...">
                    </div>

                    <div class="pt-4 border-t border-slate-700 flex gap-3">
                        <button type="submit" class="btn-rose flex-1 font-bold">End Shift</button>
                        <button type="button" wire:click="$set('selectedActiveShift', null)" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
