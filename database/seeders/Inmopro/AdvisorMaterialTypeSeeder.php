<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\AdvisorMaterialType;
use Illuminate\Database\Seeder;

class AdvisorMaterialTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'POLO', 'name' => 'Polo', 'sort_order' => 1],
            ['code' => 'GORRA', 'name' => 'Gorra', 'sort_order' => 2],
            ['code' => 'FOTOCHECK', 'name' => 'Fotocheck', 'sort_order' => 3],
        ];

        foreach ($types as $row) {
            AdvisorMaterialType::query()->updateOrCreate(
                ['code' => $row['code']],
                $row + ['is_active' => true]
            );
        }
    }
}
