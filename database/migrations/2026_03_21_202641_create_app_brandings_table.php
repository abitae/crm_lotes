<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        DB::table('app_brandings')->insert([
            'display_name' => null,
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_brandings');
    }
};
