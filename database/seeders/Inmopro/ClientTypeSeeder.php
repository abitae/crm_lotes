<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\ClientType;
use Illuminate\Database\Seeder;

class ClientTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Prospecto', 'code' => 'PROSPECTO', 'description' => 'Cliente en evaluación comercial.', 'color' => '#475569', 'sort_order' => 1],
            ['name' => 'Propio', 'code' => 'PROPIO', 'description' => 'Cliente captado y gestionado directamente por el vendedor.', 'color' => '#9333ea', 'sort_order' => 2],
            ['name' => 'Comprador final', 'code' => 'COMPRADOR_FINAL', 'description' => 'Cliente que compra para uso propio.', 'color' => '#0f766e', 'sort_order' => 3],
            ['name' => 'Inversionista', 'code' => 'INVERSIONISTA', 'description' => 'Cliente que compra como inversión.', 'color' => '#1d4ed8', 'sort_order' => 4],
            ['name' => 'Corporativo', 'code' => 'CORPORATIVO', 'description' => 'Empresa o cuenta institucional.', 'color' => '#b45309', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            ClientType::updateOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true]
            );
        }
    }
}
