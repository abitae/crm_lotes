<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_brandings', function (Blueprint $table): void {
            $table->string('tagline', 255)->nullable()->after('logo_path');
            $table->string('primary_color', 7)->nullable()->after('tagline');
            $table->string('favicon_path')->nullable()->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('app_brandings', function (Blueprint $table): void {
            $table->dropColumn(['tagline', 'primary_color', 'favicon_path']);
        });
    }
};
