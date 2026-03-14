<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'Team Norte', 'code' => 'TEAM_NORTE', 'description' => 'Cobertura comercial zona norte.', 'color' => '#0f766e', 'sort_order' => 1],
            ['name' => 'Team Centro', 'code' => 'TEAM_CENTRO', 'description' => 'Equipo principal de cierre.', 'color' => '#1d4ed8', 'sort_order' => 2],
            ['name' => 'Team Sur', 'code' => 'TEAM_SUR', 'description' => 'Cobertura comercial zona sur.', 'color' => '#b45309', 'sort_order' => 3],
            ['name' => 'Team Inversiones', 'code' => 'TEAM_INVERSIONES', 'description' => 'Cartera de inversionistas y cuentas clave.', 'color' => '#7c3aed', 'sort_order' => 4],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['code' => $team['code']],
                $team + ['is_active' => true]
            );
        }
    }
}
