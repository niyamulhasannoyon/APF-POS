<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['brand_id', 'tax_id']);
        });
    }
};
