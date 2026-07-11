<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Product Variants Catalog
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->json('option_values'); // e.g. {"Size": "L", "Color": "Red"}
            $table->decimal('cost', 10, 2);
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // Stock per Variant per Branch
        Schema::create('branch_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock_quantity', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['branch_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_variant');
        Schema::dropIfExists('product_variants');
    }
};
