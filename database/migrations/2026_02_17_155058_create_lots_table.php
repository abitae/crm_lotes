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
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('block');
            $table->unsignedInteger('number');
            $table->decimal('area', 12, 2);
            $table->decimal('price', 15, 2);
            $table->foreignId('lot_status_id')->constrained('lot_statuses')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('advisors')->nullOnDelete();
            $table->string('client_name')->nullable();
            $table->string('client_dni', 20)->nullable();
            $table->decimal('advance', 15, 2)->nullable();
            $table->decimal('remaining_balance', 15, 2)->nullable();
            $table->date('payment_limit_date')->nullable();
            $table->string('operation_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('notarial_transfer_date')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->index('project_id');
            $table->index('lot_status_id');
            $table->index('client_id');
            $table->index('advisor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
