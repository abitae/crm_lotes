<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_sales_config', function (Blueprint $table) {
            $table->id();
            $table->decimal('general_sales_goal', 15, 2)->default(0);
            $table->timestamps();
        });

        DB::table('report_sales_config')->insert([
            'general_sales_goal' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sales_config');
    }
};
