<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advisor_membership_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('advisor_membership_payments', 'advisor_membership_installment_id')) {
                $table->unsignedBigInteger('advisor_membership_installment_id')->nullable()->after('advisor_membership_id');
            }
            if (! Schema::hasColumn('advisor_membership_payments', 'cash_account_id')) {
                $table->unsignedBigInteger('cash_account_id')->nullable()->after('advisor_membership_installment_id');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            $fkExists = DB::selectOne("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = 'advisor_membership_payments' AND CONSTRAINT_NAME = 'am_payments_installment_fk'", [DB::getDatabaseName()]);
            if (! $fkExists) {
                Schema::table('advisor_membership_payments', function (Blueprint $table) {
                    $table->foreign('advisor_membership_installment_id', 'am_payments_installment_fk')->references('id')->on('advisor_membership_installments')->nullOnDelete();
                });
            }
            $fkCashExists = DB::selectOne("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = 'advisor_membership_payments' AND CONSTRAINT_NAME = 'am_payments_cash_account_fk'", [DB::getDatabaseName()]);
            if (! $fkCashExists) {
                Schema::table('advisor_membership_payments', function (Blueprint $table) {
                    $table->foreign('cash_account_id', 'am_payments_cash_account_fk')->references('id')->on('cash_accounts')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('advisor_membership_payments', function (Blueprint $table) {
                $table->dropForeign('am_payments_installment_fk');
                $table->dropForeign('am_payments_cash_account_fk');
            });
        }
        Schema::table('advisor_membership_payments', function (Blueprint $table) {
            $table->dropColumn(['advisor_membership_installment_id', 'cash_account_id']);
        });
    }
};
