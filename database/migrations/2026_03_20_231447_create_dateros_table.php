<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dateros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained('advisors')->restrictOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('dni');
            $table->string('username')->unique();
            $table->string('pin');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique('dni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dateros');
    }
};
