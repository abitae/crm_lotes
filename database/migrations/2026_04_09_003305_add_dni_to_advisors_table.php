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
            $table->string('dni', 20)->nullable()->after('name');
        });

        foreach (DB::table('advisors')->orderBy('id')->get(['id']) as $row) {
            $dni = str_pad((string) $row->id, 8, '0', STR_PAD_LEFT);
            DB::table('advisors')->where('id', $row->id)->update(['dni' => $dni]);
        }

        Schema::table('advisors', function (Blueprint $table) {
            $table->unique('dni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropUnique(['dni']);
            $table->dropColumn('dni');
        });
    }
};
