<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CazadorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_advisor_can_login_with_username_and_pin(): void
    {
        $advisor = Advisor::firstOrFail();

        $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->assertOk()
            ->assertJsonStructure(['token', 'advisor' => ['id', 'name', 'username']]);
    }

    public function test_advisor_can_update_profile_and_pin(): void
    {
        $advisor = Advisor::firstOrFail();

        $login = $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->assertOk()->json();

        $token = $login['token'];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.v1.cazador.me.update'), [
                'name' => 'Asesor API',
                'phone' => '999111222',
                'email' => $advisor->email,
                'username' => $advisor->username,
            ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.v1.cazador.me.pin.update'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])->assertOk();

        $this->assertDatabaseHas('advisors', [
            'id' => $advisor->id,
            'name' => 'Asesor API',
            'phone' => '999111222',
        ]);
    }

    public function test_login_route_is_rate_limited_per_ip(): void
    {
        Cache::flush();

        $advisor = Advisor::firstOrFail();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.v1.cazador.auth.login'), [
                'username' => $advisor->username,
                'pin' => '000000',
            ]);
        }

        $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '000000',
        ])->assertStatus(429);

        Cache::flush();
    }
}
