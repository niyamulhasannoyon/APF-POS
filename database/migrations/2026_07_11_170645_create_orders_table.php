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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('offline_id')->unique(); // UUID from offline PWA
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Cashier
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method'); // cash, card, mobile, loyalty
            $table->string('payment_status')->default('paid'); // paid, unpaid, partially_paid
            $table->string('status')->default('completed'); // completed, returned, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
