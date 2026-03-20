<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dateros', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('email')->constrained('cities')->restrictOnDelete();
        });

        $defaultCityId = DB::table('cities')->orderBy('id')->value('id');
        if ($defaultCityId !== null) {
            DB::table('dateros')->whereNull('city_id')->update(['city_id' => $defaultCityId]);
        }
    }

    public function down(): void
    {
        Schema::table('dateros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
