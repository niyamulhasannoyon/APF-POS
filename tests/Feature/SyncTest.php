<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected Branch $branch;
    protected Category $category;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a Branch
        $this->branch = Branch::create([
            'name' => 'Test Dhaka Branch',
            'address' => 'Dhaka, Bangladesh',
            'phone' => '+8801700000001',
            'status' => true
        ]);

        // 2. Create a Cashier User attached to this branch
        $this->cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => $this->branch->id
        ]);

        // 3. Create a Category
        $this->category = Category::create([
            'name' => 'Snacks',
            'slug' => 'snacks'
        ]);

        // 4. Create a Product
        $this->product = Product::create([
            'name' => 'Test Chips',
            'sku' => 'TEST-CHIPS-01',
            'barcode' => '123456789012',
            'price' => 50.00,
            'cost' => 35.00,
            'category_id' => $this->category->id,
            'status' => true
        ]);

        // 5. Initialize stock (100 units) in the branch for this product
        $this->branch->products()->attach($this->product->id, [
            'stock_quantity' => 100
        ]);

        // 6. Create a Customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '01711111111',
            'email' => 'customer@example.com',
            'loyalty_points' => 10
        ]);
    }

    /**
     * Test the pull sync API returns catalogs correctly.
     */
    public function test_can_pull_catalog_data(): void
    {
        $response = $this->actingAs($this->cashier)
            ->getJson("/api/pos/sync-pull?branch_id={$this->branch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'categories',
                'customers',
                'products',
                'server_timestamp'
            ]);

        $this->assertCount(1, $response->json('categories'));
        $this->assertCount(1, $response->json('customers'));
        $this->assertCount(1, $response->json('products'));

        // Verify product structure returns local branch stock level
        $response->assertJsonPath('products.0.stock_quantity', 100);
    }

    /**
     * Test the pull sync API handles incremental timestamp correctly.
     */
    public function test_pull_respects_last_sync_timestamp(): void
    {
        // Pull with a future timestamp - should return empty since no modifications occurred later
        $futureTime = now()->addHour()->toIso8601String();
        
        $response = $this->actingAs($this->cashier)
            ->getJson("/api/pos/sync-pull?branch_id={$this->branch->id}&last_sync=" . urlencode($futureTime));

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('categories'));
        $this->assertCount(0, $response->json('products'));
        $this->assertCount(0, $response->json('customers'));
    }

    /**
     * Test that offline transactions can be pushed and process correctly.
     */
    public function test_can_push_offline_orders_successfully(): void
    {
        $offlineId = 'test-sale-uuid-001';

        $payload = [
            'orders' => [
                [
                    'offline_id' => $offlineId,
                    'branch_id' => $this->branch->id,
                    'user_id' => $this->cashier->id,
                    'customer_id' => $this->customer->id,
                    'subtotal' => 100.00, // buying 2 chips
                    'tax_amount' => 15.00,
                    'discount_amount' => 0.00,
                    'total_amount' => 115.00,
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'created_at' => now()->subMinutes(10)->toIso8601String(),
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 2,
                            'price' => 50.00,
                            'cost' => 35.00,
                            'subtotal' => 100.00
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/pos/sync-push', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_offline_ids.0', $offlineId);

        // 1. Verify order in DB
        $this->assertDatabaseHas('orders', [
            'offline_id' => $offlineId,
            'total_amount' => 115.00,
            'customer_id' => $this->customer->id
        ]);

        // 2. Verify inventory was decremented (100 - 2 = 98)
        $stock = DB::table('branch_product')
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity');
        $this->assertEquals(98, $stock);

        // 3. Verify loyalty points were added (115 total / 10 = 11 points added, 10 + 11 = 21)
        $this->customer->refresh();
        $this->assertEquals(21, $this->customer->loyalty_points);
    }

    /**
     * Test sync push idempotency (prevents duplicate orders).
     */
    public function test_pushing_orders_is_idempotent(): void
    {
        $offlineId = 'test-sale-uuid-002';

        $payload = [
            'orders' => [
                [
                    'offline_id' => $offlineId,
                    'branch_id' => $this->branch->id,
                    'user_id' => $this->cashier->id,
                    'customer_id' => $this->customer->id,
                    'subtotal' => 50.00,
                    'tax_amount' => 7.50,
                    'discount_amount' => 0.00,
                    'total_amount' => 57.50,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'created_at' => now()->toIso8601String(),
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 1,
                            'price' => 50.00,
                            'cost' => 35.00,
                            'subtotal' => 50.00
                        ]
                    ]
                ]
            ]
        ];

        // 1st Push
        $response1 = $this->actingAs($this->cashier)
            ->postJson('/api/pos/sync-push', $payload);
        $response1->assertStatus(200)
            ->assertJsonPath('synced_offline_ids.0', $offlineId);

        // Count orders in DB
        $this->assertEquals(1, Order::where('offline_id', $offlineId)->count());

        // Get stock level
        $stock1 = DB::table('branch_product')
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity');
        $this->assertEquals(99, $stock1);

        // 2nd Push (Retry with same payload)
        $response2 = $this->actingAs($this->cashier)
            ->postJson('/api/pos/sync-push', $payload);
        
        // Should succeed but do nothing
        $response2->assertStatus(200)
            ->assertJsonPath('synced_offline_ids.0', $offlineId);

        // Order count in DB should still be 1 (prevented duplicate insertion!)
        $this->assertEquals(1, Order::where('offline_id', $offlineId)->count());

        // Stock should still be 99 (prevented duplicate deduction!)
        $stock2 = DB::table('branch_product')
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->value('stock_quantity');
        $this->assertEquals(99, $stock2);
    }
}
