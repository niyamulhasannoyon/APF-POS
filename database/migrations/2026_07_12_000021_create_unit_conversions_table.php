<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('factor', 12, 4); // how many 'to_unit' in 1 'from_unit'
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
