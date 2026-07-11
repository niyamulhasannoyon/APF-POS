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
        // Customer Groups
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->timestamps();
        });

        // Add to Customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->after('id')->constrained('customer_groups')->nullOnDelete();
            $table->decimal('store_credit', 12, 2)->default(0.00)->after('loyalty_points');
            $table->decimal('outstanding_dues', 12, 2)->default(0.00)->after('store_credit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_group_id']);
            $table->dropColumn(['customer_group_id', 'store_credit', 'outstanding_dues']);
        });
        Schema::dropIfExists('customer_groups');
    }
};
