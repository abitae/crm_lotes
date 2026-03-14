<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attention_tickets', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('advisor_id')->constrained('clients')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('client_id')->constrained('projects')->nullOnDelete();
        });

        DB::table('attention_tickets')->update([
            'client_id' => DB::raw('(select client_id from lots where lots.id = attention_tickets.lot_id)'),
            'project_id' => DB::raw('(select project_id from lots where lots.id = attention_tickets.lot_id)'),
        ]);

        Schema::table('attention_tickets', function (Blueprint $table) {
            $table->dropForeign(['lot_id']);
            $table->foreignId('lot_id')->nullable()->change();
            $table->dateTime('scheduled_at')->nullable()->change();
            $table->foreign('lot_id')->references('id')->on('lots')->nullOnDelete();
            $table->index(['project_id', 'client_id', 'status'], 'attention_tickets_project_client_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attention_tickets', function (Blueprint $table) {
            $table->dropIndex('attention_tickets_project_client_status_index');
            $table->dropForeign(['project_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['lot_id']);
            $table->dropColumn(['project_id', 'client_id']);
            $table->foreignId('lot_id')->nullable(false)->change();
            $table->dateTime('scheduled_at')->nullable(false)->change();
            $table->foreign('lot_id')->references('id')->on('lots')->cascadeOnDelete();
        });
    }
};
