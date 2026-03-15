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
        Schema::table('membership_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('months')->default(12)->after('name');
        });

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn('months');
        });
    }
};
