<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email')->unique();
            $table->string('pin')->nullable()->after('username');
            $table->boolean('is_active')->default(true)->after('pin');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn(['username', 'pin', 'is_active', 'last_login_at']);
        });
    }
};
