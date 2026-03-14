<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! ClientType::query()->exists()) {
            $this->call(ClientTypeSeeder::class);
        }
        if (! City::query()->exists()) {
            $this->call(CitySeeder::class);
        }
        if (! Advisor::query()->exists()) {
            $this->call(AdvisorSeeder::class);
        }

        $types = ClientType::orderBy('sort_order')->get()->values();
        $cities = City::orderBy('sort_order')->get()->values();
        $advisors = Advisor::orderBy('name')->get()->values();

        for ($i = 1; $i <= 100; $i++) {
            Client::updateOrCreate(
                ['dni' => (string) (20000000 + $i)],
                [
                    'name' => fake()->name(),
                    'phone' => '9800000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'email' => "cliente{$i}@correo.com",
                    'client_type_id' => $types[($i - 1) % $types->count()]->id,
                    'city_id' => $cities[($i - 1) % $cities->count()]->id,
                    'advisor_id' => $advisors[($i - 1) % $advisors->count()]->id,
                ]
            );
        }
    }
}
