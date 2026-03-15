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
        Schema::table('advisor_memberships', function (Blueprint $table) {
            $table->foreignId('membership_type_id')->nullable()->after('advisor_id')->constrained('membership_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisor_memberships', function (Blueprint $table) {
            $table->dropForeign(['membership_type_id']);
        });
    }
};
