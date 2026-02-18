<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            Client::create([
                'name' => fake()->name(),
                'dni' => (string) (20000000 + $i),
                'phone' => '9800000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => "cliente{$i}@correo.com",
            ]);
        }
    }
}
