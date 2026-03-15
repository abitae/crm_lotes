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
        Schema::create('advisor_membership_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_membership_id')->constrained('advisor_memberships')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('PENDIENTE');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['advisor_membership_id', 'sequence'], 'am_installments_membership_seq_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_membership_installments');
    }
};
