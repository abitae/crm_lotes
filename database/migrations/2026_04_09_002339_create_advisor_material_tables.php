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
        Schema::create('advisor_material_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('advisor_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained('advisors')->cascadeOnDelete();
            $table->foreignId('advisor_material_type_id')->constrained('advisor_material_types')->cascadeOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['advisor_id', 'advisor_material_type_id'], 'advisor_material_unique_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_material_items');
        Schema::dropIfExists('advisor_material_types');
    }
};
