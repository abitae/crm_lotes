<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\CommissionStatus;
use Illuminate\Database\Seeder;

class CommissionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pendiente', 'code' => 'PENDIENTE', 'color' => '#f59e0b', 'sort_order' => 1],
            ['name' => 'Pagado', 'code' => 'PAGADO', 'color' => '#10b981', 'sort_order' => 2],
        ];

        foreach ($statuses as $status) {
            CommissionStatus::firstOrCreate(['code' => $status['code']], $status);
        }
    }
}
