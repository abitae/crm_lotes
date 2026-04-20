<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dateros', function (Blueprint $table): void {
            $table->string('invite_token', 36)->nullable()->unique()->after('id');
        });

        foreach (DB::table('dateros')->select('id')->orderBy('id')->cursor() as $row) {
            DB::table('dateros')->where('id', $row->id)->update([
                'invite_token' => (string) Str::uuid(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('dateros', function (Blueprint $table): void {
            $table->dropUnique(['invite_token']);
            $table->dropColumn('invite_token');
        });
    }
};
