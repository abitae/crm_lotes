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
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->foreignId('advisor_membership_payment_id')->nullable()->after('lot_payment_id')->constrained('advisor_membership_payments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropForeign(['advisor_membership_payment_id']);
        });
    }
};
