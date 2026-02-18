<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\AdvisorLevel;
use Illuminate\Database\Seeder;

class AdvisorLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['name' => 'NIVEL 4', 'code' => 'NIVEL_4', 'direct_rate' => 7, 'pyramid_rate' => 1, 'color' => '#7c3aed', 'sort_order' => 4],
            ['name' => 'NIVEL 3', 'code' => 'NIVEL_3', 'direct_rate' => 7, 'pyramid_rate' => 1, 'color' => '#6366f1', 'sort_order' => 3],
            ['name' => 'NIVEL 2', 'code' => 'NIVEL_2', 'direct_rate' => 7, 'pyramid_rate' => 1, 'color' => '#10b981', 'sort_order' => 2],
            ['name' => 'NIVEL 1', 'code' => 'NIVEL_1', 'direct_rate' => 7, 'pyramid_rate' => 1, 'color' => '#3b82f6', 'sort_order' => 1],
        ];

        foreach ($levels as $level) {
            AdvisorLevel::firstOrCreate(['code' => $level['code']], $level);
        }
    }
}
