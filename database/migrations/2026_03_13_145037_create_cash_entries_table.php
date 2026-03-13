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
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnDelete();
            $table->foreignId('lot_payment_id')->nullable()->constrained('lot_payments')->nullOnDelete();
            $table->string('type', 20);
            $table->string('concept');
            $table->decimal('amount', 15, 2);
            $table->date('entry_date');
            $table->string('reference', 120)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['cash_account_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
