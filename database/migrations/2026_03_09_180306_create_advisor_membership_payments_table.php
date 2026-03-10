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
        Schema::create('advisor_membership_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_membership_id')->constrained('advisor_memberships')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->comment('Monto del abono');
            $table->date('paid_at')->comment('Fecha de pago');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_membership_payments');
    }
};
