<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'kind', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assets');
    }
};
