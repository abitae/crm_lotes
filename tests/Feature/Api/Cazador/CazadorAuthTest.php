<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
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

    public function test_advisor_can_login_with_reset_pin_from_admin(): void
    {
        $advisor = Advisor::firstOrFail();
        $advisor->update(['pin' => '654321']);
        $advisor->refresh();

        $this->actingAs(User::factory()->create());
        $this->post(route('inmopro.advisors.reset-pin', $advisor))
            ->assertRedirect(route('inmopro.advisors.index'));

        $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->fresh()->username,
            'pin' => '123456',
        ])->assertOk()
            ->assertJsonStructure(['token', 'advisor' => ['id', 'name', 'username']]);
    }
}
