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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('advisor_id')->constrained('advisors')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('type');
            $table->foreignId('commission_status_id')->constrained('commission_statuses')->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();
            $table->index('lot_id');
            $table->index('advisor_id');
            $table->index('commission_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
