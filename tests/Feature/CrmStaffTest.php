<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\StaffShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class CrmStaffTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Outlet', 'address' => 'Mall', 'phone' => '123', 'status' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apfpos.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $this->branch->id
        ]);
    }

    /**
     * Test Customer CRM tiers, loyalty, credit adjustments, and audits.
     */
    public function test_crm_tiers_loyalty_and_credit_transactions(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a tiered group
        Livewire::test('⚡customer-crm')
            ->set('groupName', 'VIP Tier')
            ->set('discountPercentage', 15.00)
            ->call('saveGroup');

        $this->assertDatabaseHas('customer_groups', [
            'name' => 'VIP Tier',
            'discount_percentage' => 15.00
        ]);

        $group = CustomerGroup::first();

        // 2. Register customer and associate group
        $customer = Customer::create([
            'name' => 'Alice Margatroid',
            'email' => 'alice@gmail.com',
            'phone' => '555-888',
            'customer_group_id' => $group->id,
            'loyalty_points' => 100
        ]);

        // 3. Adjust customer balance
        Livewire::test('⚡customer-crm')
            ->call('openAdjustment', $customer->id)
            ->set('adjType', 'credit_added')
            ->set('adjAmount', 150.00)
            ->set('adjNotes', 'Gift card loading')
            ->call('submitAdjustment');

        $customer->refresh();
        $this->assertEquals(150.00, $customer->store_credit);

        // Verify transaction logged
        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'type' => 'credit_added',
            'amount' => 150.00,
            'notes' => 'Gift card loading'
        ]);
    }

    /**
     * Test staff shifts, attendance cash, and user audit logs.
     */
    public function test_staff_shifts_and_audit_logging(): void
    {
        $this->actingAs($this->admin);

        // Open shift register
        Livewire::test('⚡staff-manager')
            ->set('startCash', 150.00)
            ->set('shiftNotes', 'Morning register start')
            ->call('startShiftSubmit');

        $this->assertDatabaseHas('staff_shifts', [
            'user_id' => $this->admin->id,
            'start_cash_balance' => 150.00,
            'status' => 'open'
        ]);

        $shift = StaffShift::first();

        // Close shift register
        Livewire::test('⚡staff-manager')
            ->call('openCloseShift', $shift->id)
            ->set('endCash', 240.00)
            ->set('shiftNotes', 'No drawer discrepancies')
            ->call('closeShiftSubmit');

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(240.00, $shift->end_cash_balance);

        // Verify system generated activity logs
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'shift_start'
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'shift_end'
        ]);
    }

    /**
     * Test cashier sales commission calculations on order sync.
     */
    public function test_cashier_sales_commission_calculations(): void
    {
        // Create cashier user with 5% commission rate
        $cashier = User::create([
            'name' => 'Cashier Bob',
            'email' => 'bob@apfpos.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
            'commission_rate' => 5.00
        ]);

        $category = \App\Models\Category::create(['name' => 'General', 'slug' => 'general']);
        $product = \App\Models\Product::create([
            'name' => 'Test Item',
            'sku' => 'TEST-01',
            'barcode' => '777888',
            'price' => 10.00,
            'cost' => 6.00,
            'category_id' => $category->id,
            'status' => true
        ]);

        // Mock synchronized push request from POS terminal
        $payload = [
            'orders' => [
                [
                    'offline_id' => 'abc-uuid-1234',
                    'branch_id' => $this->branch->id,
                    'user_id' => $cashier->id,
                    'subtotal' => 100.00,
                    'tax_amount' => 5.00,
                    'discount_amount' => 0.00,
                    'total_amount' => 105.00,
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 10,
                            'price' => 10.00,
                            'cost' => 6.00,
                            'subtotal' => 100.00
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/pos/sync-push', $payload);

        $response->assertStatus(200);

        // Verify order is logged
        $this->assertDatabaseHas('orders', ['offline_id' => 'abc-uuid-1234']);

        // Verify commission is logged: 5% of $105 = $5.25
        $this->assertDatabaseHas('sales_commissions', [
            'user_id' => $cashier->id,
            'commission_amount' => 5.25,
            'status' => 'pending'
        ]);
    }
}
