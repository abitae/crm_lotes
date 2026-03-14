<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'abel.arana@hotmail.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('lobomalo123'),
            ]
        );

        $this->call([
            Inmopro\AdvisorLevelSeeder::class,
            Inmopro\LotStatusSeeder::class,
            Inmopro\CommissionStatusSeeder::class,
            Inmopro\ProjectSeeder::class,
            Inmopro\TeamSeeder::class,
            Inmopro\ClientTypeSeeder::class,
            Inmopro\CitySeeder::class,
            Inmopro\AdvisorSeeder::class,
            Inmopro\ClientSeeder::class,
            Inmopro\LotSeeder::class,
            Inmopro\LotPreReservationSeeder::class,
        ]);
    }
}
