<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class PurchasingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;
    protected Category $category;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Warehouse', 'address' => 'HQ', 'phone' => '111', 'status' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apfpos.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $this->branch->id
        ]);

        $this->category = Category::create(['name' => 'Groceries', 'slug' => 'groceries']);
        $this->productA = Product::create([
            'name' => 'Salt 1kg',
            'sku' => 'SALT-001',
            'barcode' => '11112222',
            'price' => 2.00,
            'cost' => 1.20,
            'category_id' => $this->category->id,
            'status' => true
        ]);
        $this->productB = Product::create([
            'name' => 'Sugar 1kg',
            'sku' => 'SUGAR-001',
            'barcode' => '33334444',
            'price' => 3.00,
            'cost' => 1.80,
            'category_id' => $this->category->id,
            'status' => true
        ]);
    }

    /**
     * Test Supplier CRUD and payment balance tracking.
     */
    public function test_can_manage_suppliers_and_log_payments(): void
    {
        $this->actingAs($this->admin);

        // Create Supplier via Livewire
        Livewire::test('⚡supplier-directory')
            ->set('name', 'Universal Distributors')
            ->set('contactName', 'John Doe')
            ->set('email', 'john@universal.com')
            ->set('phone', '555-1234')
            ->set('paymentTerms', 'net30')
            ->call('saveSupplier');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Universal Distributors',
            'payment_terms' => 'net30',
            'outstanding_balance' => 0.00
        ]);

        $supplier = Supplier::first();

        // Artificially increase dues to test payment
        $supplier->update(['outstanding_balance' => 500.00]);

        // Pay dues via Livewire
        Livewire::test('⚡supplier-directory')
            ->call('openPayModal', $supplier->id)
            ->set('payAmount', 200.00)
            ->set('payMethod', 'bank_transfer')
            ->set('payNotes', 'Paid partial invoice ref #9898')
            ->call('submitPayment');

        // Verify outstanding balance reduced
        $supplier->refresh();
        $this->assertEquals(300.00, $supplier->outstanding_balance);

        // Verify payment is logged
        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount_paid' => 200.00,
            'payment_method' => 'bank_transfer',
            'notes' => 'Paid partial invoice ref #9898'
        ]);
    }

    /**
     * Test Purchase Order Workflow & Goods Receiving inventory increments.
     */
    public function test_purchase_order_flow_and_goods_receiving(): void
    {
        $this->actingAs($this->admin);

        $supplier = Supplier::create([
            'name' => 'Organic Foods Ltd',
            'payment_terms' => 'cash',
            'outstanding_balance' => 0.00
        ]);

        // Build PO draft and submit
        Livewire::test('⚡purchase-manager')
            ->set('supplierId', $supplier->id)
            ->set('branchId', $this->branch->id)
            ->set('selectedProductId', $this->productA->id)
            ->set('qtyOrdered', 10)
            ->set('unitCost', 1.20)
            ->call('addToDraft')
            ->set('selectedProductId', $this->productB->id)
            ->set('qtyOrdered', 20)
            ->set('unitCost', 1.80)
            ->call('addToDraft')
            ->set('paidAmount', 10.00) // $12 + $36 = $48 total, $10 paid -> $38 unpaid
            ->set('notes', 'Initial stock replenishment')
            ->call('savePurchaseOrder');

        // Verify purchase order created
        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertEquals('ordered', $po->status);
        $this->assertEquals(48.00, $po->total_amount);
        $this->assertEquals(10.00, $po->paid_amount);

        // Verify PO items created
        $this->assertCount(2, $po->items);

        // Verify initial branch stock level is empty/zero
        $stockA = DB::table('branch_product')->where('branch_id', $this->branch->id)->where('product_id', $this->productA->id)->value('stock_quantity') ?? 0;
        $this->assertEquals(0, $stockA);

        // Receive goods
        Livewire::test('⚡purchase-manager')
            ->call('openReceiveModal', $po->id)
            ->set('receiveItems.0.qty_received', 10)
            ->set('receiveItems.1.qty_received', 20)
            ->call('submitReceive');

        // Verify PO status updated
        $po->refresh();
        $this->assertEquals('completed', $po->status);

        // Verify branch stock incremented
        $stockA = DB::table('branch_product')->where('branch_id', $this->branch->id)->where('product_id', $this->productA->id)->value('stock_quantity');
        $stockB = DB::table('branch_product')->where('branch_id', $this->branch->id)->where('product_id', $this->productB->id)->value('stock_quantity');
        $this->assertEquals(10, $stockA);
        $this->assertEquals(20, $stockB);

        // Verify supplier outstanding dues incremented by unpaid amount ($38.00)
        $supplier->refresh();
        $this->assertEquals(38.00, $supplier->outstanding_balance);
    }
}
