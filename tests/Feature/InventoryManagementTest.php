<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create(['name' => 'Main Branch', 'address' => 'HQ', 'phone' => '123', 'status' => true]);
        $this->branchB = Branch::create(['name' => 'Outlet Branch', 'address' => 'Mall', 'phone' => '456', 'status' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apfpos.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $this->branchA->id
        ]);

        $this->category = Category::create(['name' => 'Beverages', 'slug' => 'beverages']);
        $this->product = Product::create([
            'name' => 'Premium Coffee Beans',
            'sku' => 'COFFEE-001',
            'barcode' => '888123456789',
            'price' => 12.00,
            'cost' => 8.00,
            'category_id' => $this->category->id,
            'status' => true
        ]);
    }

    /**
     * Test product variants can be created and synced.
     */
    public function test_can_sync_product_variants_and_stock(): void
    {
        // Create Variant
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'COFFEE-001-DARK',
            'barcode' => '888123456799',
            'option_values' => ['Roast' => 'Dark'],
            'cost' => 8.50,
            'price' => 13.00
        ]);

        // Map variant stock level
        DB::table('branch_variant')->insert([
            'branch_id' => $this->branchA->id,
            'product_variant_id' => $variant->id,
            'stock_quantity' => 50,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Call sync pull API
        $response = $this->actingAs($this->admin)
            ->getJson("/api/pos/sync-pull?branch_id={$this->branchA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('products.0.variants'));
        $this->assertEquals(50, $response->json('products.0.variants.0.stock_quantity'));
        $this->assertEquals('Dark', $response->json('products.0.variants.0.option_values.Roast'));
    }

    /**
     * Test manual stock adjustment audit logging with reason codes.
     */
    public function test_manual_stock_adjustments_log_reason_codes(): void
    {
        // Set initial stock
        DB::table('branch_product')->insert([
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'stock_quantity' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Test Livewire component stock adjustment
        $this->actingAs($this->admin);
        
        Livewire::test('⚡stock-adjuster')
            ->call('open', $this->product->id, $this->product->name)
            ->set('branchId', $this->branchA->id)
            ->set('quantity', 15) // adjusted +5 delta
            ->set('reasonCode', 'damage')
            ->set('notes', 'Damaged bag replacement')
            ->call('save');

        // Confirm database contains the adjustment log
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $this->product->id,
            'branch_id' => $this->branchA->id,
            'quantity_adjusted' => 5,
            'reason_code' => 'damage',
            'notes' => 'Damaged bag replacement'
        ]);

        // Confirm branch stock is updated
        $this->assertEquals(15, DB::table('branch_product')
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity'));
    }

    /**
     * Test bulk CSV import.
     */
    public function test_can_bulk_import_products_from_csv(): void
    {
        // Mock a CSV file
        $csvContent = "ID,Name,SKU,Barcode,Category,Cost Price,Selling Price,Status\n" .
                      ",Organic Green Tea,TEA-001,7771234567,Beverages,3.00,5.50,Active\n" .
                      ",Whole Wheat Bread,BREAD-02,9991234567,Bakery,1.50,2.80,Active\n";

        $file = UploadedFile::fake()->createWithContent('catalog.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post('/admin/products/import', [
                'csv_file' => $file
            ]);

        $response->assertRedirect();
        
        // Assert items are registered
        $this->assertDatabaseHas('products', ['sku' => 'TEA-001', 'name' => 'Organic Green Tea']);
        $this->assertDatabaseHas('products', ['sku' => 'BREAD-02', 'name' => 'Whole Wheat Bread']);
        
        // Assert Category 'Bakery' was created dynamically
        $this->assertDatabaseHas('categories', ['name' => 'Bakery']);
    }

    /**
     * Test stock transfer lifecycle.
     */
    public function test_stock_transfer_lifecycle(): void
    {
        // Set initial stock at branch A
        DB::table('branch_product')->insert([
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'stock_quantity' => 100,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->actingAs($this->admin);

        // Test Livewire StockTransferManager
        $component = Livewire::test('⚡stock-transfer-manager')
            ->set('fromBranchId', $this->branchA->id)
            ->set('toBranchId', $this->branchB->id)
            ->set('productId', $this->product->id)
            ->set('quantity', 20)
            ->set('notes', 'Transfer requested for promo')
            ->call('createTransfer');

        // Verify transfer request is created
        $transfer = \App\Models\StockTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertEquals('pending', $transfer->status);

        // Ship transfer
        $component->call('shipTransfer', $transfer->id);

        // Confirm stock is decremented at branch A
        $this->assertEquals(80, DB::table('branch_product')
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity'));

        // Verify status changes to shipped
        $transfer->refresh();
        $this->assertEquals('shipped', $transfer->status);

        // Receive transfer
        $component->call('receiveTransfer', $transfer->id);

        // Confirm stock is added at branch B
        $this->assertEquals(20, DB::table('branch_product')
            ->where('branch_id', $this->branchB->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity'));

        // Verify status changes to completed
        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
    }
}
