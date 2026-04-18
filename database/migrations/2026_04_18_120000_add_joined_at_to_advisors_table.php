<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->date('joined_at')->nullable()->after('birth_date');
        });

        DB::table('advisors')->whereNull('joined_at')->update([
            'joined_at' => DB::raw('DATE(created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn('joined_at');
        });
    }
};
