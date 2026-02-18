<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\LotStatus;
use Illuminate\Database\Seeder;

class LotStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Libre', 'code' => 'LIBRE', 'color' => '#10b981', 'sort_order' => 1],
            ['name' => 'Reservado', 'code' => 'RESERVADO', 'color' => '#f59e0b', 'sort_order' => 2],
            ['name' => 'Transferido', 'code' => 'TRANSFERIDO', 'color' => '#64748b', 'sort_order' => 3],
            ['name' => 'Cuotas', 'code' => 'CUOTAS', 'color' => '#6366f1', 'sort_order' => 4],
        ];

        foreach ($statuses as $status) {
            LotStatus::firstOrCreate(['code' => $status['code']], $status);
        }
    }
}
