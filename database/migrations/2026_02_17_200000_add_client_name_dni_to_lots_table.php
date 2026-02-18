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
        Schema::table('lots', function (Blueprint $table) {
            if (! Schema::hasColumn('lots', 'client_name')) {
                $table->string('client_name')->nullable()->after('advisor_id');
            }
            if (! Schema::hasColumn('lots', 'client_dni')) {
                $table->string('client_dni', 20)->nullable()->after('client_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'client_name')) {
                $table->dropColumn('client_name');
            }
            if (Schema::hasColumn('lots', 'client_dni')) {
                $table->dropColumn('client_dni');
            }
        });
    }
};
