<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Branches
        $branches = [
            ['name' => 'Main Branch (Dhaka)', 'address' => 'Mirpur-10, Dhaka', 'phone' => '+8801700000001'],
            ['name' => 'Chittagong Branch', 'address' => 'GEC Circle, Chittagong', 'phone' => '+8801700000002'],
            ['name' => 'Sylhet Branch', 'address' => 'Zindabazar, Sylhet', 'phone' => '+8801700000003'],
        ];

        $branchModels = [];
        foreach ($branches as $branch) {
            $branchModels[] = Branch::create($branch);
        }

        // 2. Seed Users
        User::create([
            'name' => 'POS Admin',
            'email' => 'admin@apfpos.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => null,
        ]);

        User::create([
            'name' => 'Branch Manager',
            'email' => 'manager@apfpos.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'branch_id' => $branchModels[0]->id,
        ]);

        User::create([
            'name' => 'Cashier Dhaka',
            'email' => 'cashier1@apfpos.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'branch_id' => $branchModels[0]->id,
        ]);

        User::create([
            'name' => 'Cashier Chittagong',
            'email' => 'cashier2@apfpos.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'branch_id' => $branchModels[1]->id,
        ]);

        // 3. Seed Categories
        $categories = [
            ['name' => 'Beverages', 'slug' => 'beverages'],
            ['name' => 'Snacks & Chips', 'slug' => 'snacks-chips'],
            ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs'],
            ['name' => 'Bakery & Bread', 'slug' => 'bakery-bread'],
            ['name' => 'Meat & Seafood', 'slug' => 'meat-seafood'],
            ['name' => 'Produce & Fruits', 'slug' => 'produce-fruits'],
            ['name' => 'Household Care', 'slug' => 'household-care'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = Category::create($cat);
        }

        // 4. Seed Products
        $productsData = [
            // Beverages
            ['name' => 'Coca-Cola 250ml', 'sku' => 'BEV-COKE-250', 'barcode' => '5449000000996', 'price' => 35.00, 'cost' => 28.00, 'category' => 'beverages', 'image_url' => null],
            ['name' => 'Pepsi 250ml', 'sku' => 'BEV-PEPSI-250', 'barcode' => '012000000133', 'price' => 35.00, 'cost' => 27.50, 'category' => 'beverages', 'image_url' => null],
            ['name' => 'Mineral Water 500ml', 'sku' => 'BEV-WATER-500', 'barcode' => '834729103829', 'price' => 20.00, 'cost' => 12.00, 'category' => 'beverages', 'image_url' => null],
            ['name' => 'Orange Juice 1L', 'sku' => 'BEV-OJUICE-1L', 'barcode' => '739482710394', 'price' => 180.00, 'cost' => 140.00, 'category' => 'beverages', 'image_url' => null],
            ['name' => 'Red Bull 250ml', 'sku' => 'BEV-REDBULL-250', 'barcode' => '9002490100070', 'price' => 250.00, 'cost' => 210.00, 'category' => 'beverages', 'image_url' => null],

            // Snacks & Chips
            ['name' => 'Potato Chips 150g', 'sku' => 'SNA-CHIPS-150', 'barcode' => '8901425103429', 'price' => 80.00, 'cost' => 60.00, 'category' => 'snacks-chips', 'image_url' => null],
            ['name' => 'Salted Peanuts 200g', 'sku' => 'SNA-PEANUTS-200', 'barcode' => '8941038472913', 'price' => 120.00, 'cost' => 90.00, 'category' => 'snacks-chips', 'image_url' => null],
            ['name' => 'Chocolate Chip Cookies', 'sku' => 'SNA-COOKIES-001', 'barcode' => '074381029342', 'price' => 150.00, 'cost' => 110.00, 'category' => 'snacks-chips', 'image_url' => null],
            ['name' => 'Tortilla Chips 200g', 'sku' => 'SNA-TORTILLA-200', 'barcode' => '028400070546', 'price' => 140.00, 'cost' => 105.00, 'category' => 'snacks-chips', 'image_url' => null],

            // Dairy & Eggs
            ['name' => 'Fresh Milk 1L', 'sku' => 'DAI-MILK-1L', 'barcode' => '8941100293049', 'price' => 90.00, 'cost' => 75.00, 'category' => 'dairy-eggs', 'image_url' => null],
            ['name' => 'Brown Eggs 12pcs', 'sku' => 'DAI-EGGS-12', 'barcode' => '8942938471029', 'price' => 145.00, 'cost' => 120.00, 'category' => 'dairy-eggs', 'image_url' => null],
            ['name' => 'Salted Butter 200g', 'sku' => 'DAI-BUTTER-200', 'barcode' => '8941029348123', 'price' => 280.00, 'cost' => 230.00, 'category' => 'dairy-eggs', 'image_url' => null],
            ['name' => 'Cheddar Cheese 250g', 'sku' => 'DAI-CHEESE-250', 'barcode' => '076123049852', 'price' => 380.00, 'cost' => 310.00, 'category' => 'dairy-eggs', 'image_url' => null],

            // Bakery & Bread
            ['name' => 'Sliced White Bread 500g', 'sku' => 'BAK-BREAD-500', 'barcode' => '8941019283749', 'price' => 60.00, 'cost' => 45.00, 'category' => 'bakery-bread', 'image_url' => null],
            ['name' => 'Butter Croissant 1pc', 'sku' => 'BAK-CROISSANT-01', 'barcode' => '1029384756201', 'price' => 75.00, 'cost' => 50.00, 'category' => 'bakery-bread', 'image_url' => null],
            ['name' => 'Chocolate Muffin 1pc', 'sku' => 'BAK-MUFFIN-CHOCO', 'barcode' => '1029384756202', 'price' => 85.00, 'cost' => 60.00, 'category' => 'bakery-bread', 'image_url' => null],

            // Meat & Seafood
            ['name' => 'Beef Ribeye Steak 1kg', 'sku' => 'MEA-RIBEYE-1K', 'barcode' => '2001029340001', 'price' => 950.00, 'cost' => 750.00, 'category' => 'meat-seafood', 'image_url' => null],
            ['name' => 'Chicken Breast 1kg', 'sku' => 'MEA-CHICKEN-1K', 'barcode' => '2001029340002', 'price' => 380.00, 'cost' => 300.00, 'category' => 'meat-seafood', 'image_url' => null],
            ['name' => 'Salmon Fillet 500g', 'sku' => 'MEA-SALMON-500', 'barcode' => '2001029340003', 'price' => 850.00, 'cost' => 690.00, 'category' => 'meat-seafood', 'image_url' => null],

            // Produce & Fruits
            ['name' => 'Fuji Apples 1kg', 'sku' => 'PRO-APPLE-1K', 'barcode' => '2002019280001', 'price' => 240.00, 'cost' => 190.00, 'category' => 'produce-fruits', 'image_url' => null],
            ['name' => 'Cavendish Bananas 1kg', 'sku' => 'PRO-BANANA-1K', 'barcode' => '2002019280002', 'price' => 120.00, 'cost' => 90.00, 'category' => 'produce-fruits', 'image_url' => null],
            ['name' => 'Red Tomatoes 1kg', 'sku' => 'PRO-TOMATO-1K', 'barcode' => '2002019280003', 'price' => 100.00, 'cost' => 75.00, 'category' => 'produce-fruits', 'image_url' => null],

            // Household Care
            ['name' => 'Dishwashing Liquid 500ml', 'sku' => 'HOU-DISHSOP-500', 'barcode' => '8941029384729', 'price' => 125.00, 'cost' => 98.00, 'category' => 'household-care', 'image_url' => null],
            ['name' => 'Laundry Detergent 2L', 'sku' => 'HOU-DETERG-2L', 'barcode' => '0729384758201', 'price' => 450.00, 'cost' => 360.00, 'category' => 'household-care', 'image_url' => null],
            ['name' => 'Toilet Paper 12 rolls', 'sku' => 'HOU-TP-12', 'barcode' => '0729384758202', 'price' => 320.00, 'cost' => 240.00, 'category' => 'household-care', 'image_url' => null],
        ];

        $productModels = [];
        foreach ($productsData as $prod) {
            $catModel = $categoryModels[$prod['category']];
            $prodModel = Product::create([
                'name' => $prod['name'],
                'sku' => $prod['sku'],
                'barcode' => $prod['barcode'],
                'price' => $prod['price'],
                'cost' => $prod['cost'],
                'category_id' => $catModel->id,
                'image_url' => $prod['image_url'],
                'status' => true,
            ]);
            $productModels[] = $prodModel;
        }

        // 5. Seed Inventory Levels per Branch (branch_product)
        foreach ($branchModels as $branch) {
            foreach ($productModels as $prod) {
                // Attach product with random stock levels between 10 and 150
                $branch->products()->attach($prod->id, [
                    'stock_quantity' => rand(15, 150),
                ]);
            }
        }

        // 6. Seed Initial Customers
        $customers = [
            ['name' => 'Niyamul Hasan', 'phone' => '01712345678', 'email' => 'niyamul@example.com', 'loyalty_points' => 150],
            ['name' => 'John Doe', 'phone' => '01812345678', 'email' => 'john@example.com', 'loyalty_points' => 45],
            ['name' => 'Jane Smith', 'phone' => '01912345678', 'email' => 'jane@example.com', 'loyalty_points' => 10],
            ['name' => 'Zakir Hossain', 'phone' => '01512345678', 'email' => 'zakir@example.com', 'loyalty_points' => 300],
            ['name' => 'Aisha Rahman', 'phone' => '01612345678', 'email' => 'aisha@example.com', 'loyalty_points' => 0],
        ];

        foreach ($customers as $cust) {
            Customer::create($cust);
        }
    }
}
