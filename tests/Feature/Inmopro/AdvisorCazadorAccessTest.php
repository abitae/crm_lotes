<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorApiToken;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdvisorCazadorAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_authenticated_user_can_update_advisor_username_only(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $originalHash = $advisor->getRawOriginal('pin');

        $this->actingAs($user)
            ->put(route('inmopro.advisors.cazador-access.update', $advisor), [
                'username' => 'usuario_cazador_unico_'.uniqid(),
                'pin' => '',
                'pin_confirmation' => '',
            ])
            ->assertRedirect(route('inmopro.advisors.index'));

        $advisor->refresh();
        $this->assertStringStartsWith('usuario_cazador_unico_', $advisor->username);
        $this->assertSame($originalHash, $advisor->getRawOriginal('pin'));
    }

    public function test_authenticated_user_can_update_advisor_pin_and_revokes_api_tokens(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();

        AdvisorApiToken::create([
            'advisor_id' => $advisor->id,
            'name' => 'Test',
            'token' => hash('sha256', 'plain-token-test'),
            'last_used_at' => null,
            'expires_at' => null,
        ]);

        $this->assertDatabaseCount('advisor_api_tokens', 1);

        $this->actingAs($user)
            ->put(route('inmopro.advisors.cazador-access.update', $advisor), [
                'username' => $advisor->username,
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertRedirect(route('inmopro.advisors.index'));

        $advisor->refresh();
        $this->assertTrue(Hash::check('654321', $advisor->getRawOriginal('pin')));
        $this->assertDatabaseCount('advisor_api_tokens', 0);
    }

    public function test_username_must_be_unique_among_advisors(): void
    {
        $user = User::factory()->create();
        $advisors = Advisor::query()->orderBy('id')->take(2)->get();
        $target = $advisors->first();
        $other = $advisors->last();
        $this->assertNotSame($target->id, $other->id);

        $this->actingAs($user)
            ->put(route('inmopro.advisors.cazador-access.update', $target), [
                'username' => $other->username,
                'pin' => '',
                'pin_confirmation' => '',
            ])
            ->assertSessionHasErrors(['username']);
    }
}
