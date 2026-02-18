<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            ['name' => 'Villa Norte - Mito', 'location' => 'Mito', 'total_lots' => 45, 'blocks' => ['A', 'B', 'C']],
            ['name' => 'San Antonio 3', 'location' => 'Orcotuna', 'total_lots' => 80, 'blocks' => ['A', 'B', 'C', 'D', 'E', 'F']],
            ['name' => 'Mirador 3.1', 'location' => 'Huancayo', 'total_lots' => 50, 'blocks' => ['A', 'B']],
            ['name' => 'Residencial Los Olivos', 'location' => 'Concepción', 'total_lots' => 120, 'blocks' => ['A', 'B', 'C', 'D', 'E', 'F', 'G']],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['name' => $project['name'], 'location' => $project['location']],
                $project
            );
        }
    }
}
