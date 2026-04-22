<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permite varias filas por tipo de material (historial de entregas).
     */
    public function up(): void
    {
        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->dropForeign(['advisor_id']);
            $table->dropForeign(['advisor_material_type_id']);
        });

        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->dropUnique('advisor_material_unique_type');
        });

        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->foreign('advisor_id')->references('id')->on('advisors')->cascadeOnDelete();
            $table->foreign('advisor_material_type_id')->references('id')->on('advisor_material_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->dropForeign(['advisor_id']);
            $table->dropForeign(['advisor_material_type_id']);
        });

        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->unique(['advisor_id', 'advisor_material_type_id'], 'advisor_material_unique_type');
        });

        Schema::table('advisor_material_items', function (Blueprint $table): void {
            $table->foreign('advisor_id')->references('id')->on('advisors')->cascadeOnDelete();
            $table->foreign('advisor_material_type_id')->references('id')->on('advisor_material_types')->cascadeOnDelete();
        });
    }
};
