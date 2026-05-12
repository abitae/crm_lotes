<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_profile_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advisor_profile_id')->constrained('advisor_profiles')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['advisor_profile_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_profile_documents');
    }
};
