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
        Schema::create('lot_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('lot_installment_id')->nullable()->constrained('lot_installments')->nullOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->string('payment_method', 30);
            $table->string('reference', 120)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_payments');
    }
};
