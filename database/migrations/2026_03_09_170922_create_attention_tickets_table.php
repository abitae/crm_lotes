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
        Schema::create('attention_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained('advisors')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('status', 20)->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attention_tickets');
    }
};
