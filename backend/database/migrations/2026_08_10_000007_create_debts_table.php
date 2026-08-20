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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unique('invoice_id');

            $table->decimal('amount', 10, 2);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->softDeletes();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
