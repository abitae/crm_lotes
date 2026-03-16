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
        Schema::table('lot_statuses', function (Blueprint $table): void {
            $table->unique('code');
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->dropForeign(['lot_status_id']);
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->foreign('lot_status_id')
                ->references('id')
                ->on('lot_statuses')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table): void {
            $table->dropForeign(['lot_status_id']);
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->foreign('lot_status_id')
                ->references('id')
                ->on('lot_statuses')
                ->cascadeOnDelete();
        });

        Schema::table('lot_statuses', function (Blueprint $table): void {
            $table->dropUnique(['code']);
        });
    }
};
