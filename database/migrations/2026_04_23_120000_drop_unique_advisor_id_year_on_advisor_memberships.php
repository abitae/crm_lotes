<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisor_memberships', function (Blueprint $table) {
            // El UNIQUE(advisor_id, year) es el índice que usa InnoDB para la FK de advisor_id;
            // hay que añadir un índice explícito en advisor_id antes de quitar el único.
            $table->index('advisor_id', 'advisor_memberships_advisor_id_index');
        });
        Schema::table('advisor_memberships', function (Blueprint $table) {
            $table->dropUnique(['advisor_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('advisor_memberships', function (Blueprint $table) {
            $table->unique(['advisor_id', 'year']);
        });
        Schema::table('advisor_memberships', function (Blueprint $table) {
            $table->dropIndex('advisor_memberships_advisor_id_index');
        });
    }
};
