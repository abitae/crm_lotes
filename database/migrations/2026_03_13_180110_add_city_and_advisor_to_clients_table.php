<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('client_type_id')->constrained('cities')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->after('city_id')->constrained('advisors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('advisor_id');
        });
    }
};
