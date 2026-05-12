<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advisor_id')->unique()->constrained('advisors')->cascadeOnDelete();
            $table->text('professional_profile')->nullable();
            $table->text('skills_strengths')->nullable();
            $table->text('availability')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_profiles');
    }
};
