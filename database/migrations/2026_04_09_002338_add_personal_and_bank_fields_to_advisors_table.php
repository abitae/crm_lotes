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
        Schema::table('advisors', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('name');
            $table->string('first_name', 120)->nullable()->after('birth_date');
            $table->string('last_name', 120)->nullable()->after('first_name');
            $table->string('bank_name', 120)->nullable()->after('last_name');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_cci', 30)->nullable()->after('bank_account_number');
        });

        foreach (DB::table('advisors')->select('id', 'name')->get() as $row) {
            DB::table('advisors')->where('id', $row->id)->update([
                'first_name' => $row->name,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'first_name',
                'last_name',
                'bank_name',
                'bank_account_number',
                'bank_cci',
            ]);
        });
    }
};
