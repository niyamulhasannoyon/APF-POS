<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class InventoryImportExportController extends Controller
{
    /**
     * Export the catalog to CSV.
     */
    public function export()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=apf_catalog_export_" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $products = Product::with('category')->get();

        $callback = function() use($products) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, ['ID', 'Name', 'SKU', 'Barcode', 'Category', 'Cost Price', 'Selling Price', 'Status']);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->barcode,
                    $product->category->name ?? 'Uncategorized',
                    $product->cost,
                    $product->price,
                    $product->status ? 'Active' : 'Inactive'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import catalog products from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();
        
        $handle = fopen($filePath, 'r');
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            // Row format: [ID, Name, SKU, Barcode, Category, Cost, Price, Status]
            if (count($row) < 7) continue;

            $name = $row[1];
            $sku = $row[2];
            $barcode = $row[3] ?: null;
            $categoryName = $row[4];
            $cost = floatval($row[5]);
            $price = floatval($row[6]);
            $status = (strtolower($row[7] ?? 'active') === 'active') ? 1 : 0;

            // Find or create Category
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );

            // Update or Create Product by SKU
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'barcode' => $barcode,
                    'category_id' => $category->id,
                    'cost' => $cost,
                    'price' => $price,
                    'status' => $status
                ]
            );

            $count++;
        }
        fclose($handle);

        return redirect()->back()->with('success', "Successfully imported {$count} products from CSV.");
    }
}
