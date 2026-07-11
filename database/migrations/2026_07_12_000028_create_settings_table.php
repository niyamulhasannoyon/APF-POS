<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general'); // general, invoice, pos, notification, backup, api
            $table->string('key');
            $table->text('value')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete(); // null = global
            $table->timestamps();

            $table->unique(['group', 'key', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
