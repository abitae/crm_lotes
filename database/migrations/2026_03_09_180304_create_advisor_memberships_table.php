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
        Schema::create('advisor_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained('advisors')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('Año de la membresía');
            $table->decimal('amount', 12, 2)->comment('Monto total anual a pagar');
            $table->timestamps();

            $table->unique(['advisor_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_memberships');
    }
};
