<?php

namespace Tests\Feature\Inmopro;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InmoproRbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_receives_403_on_inmopro_route(): void
    {
        Permission::findOrCreate('inmopro.dashboard', 'web');

        $user = User::factory()->create();
        $user->syncRoles([]);

        $this->actingAs($user)
            ->get(route('inmopro.dashboard'))
            ->assertForbidden();
    }

    public function test_user_with_matching_permission_can_access_inmopro_route(): void
    {
        Permission::findOrCreate('inmopro.dashboard', 'web');

        $user = User::factory()->create();
        $user->syncRoles([]);
        $user->givePermissionTo('inmopro.dashboard');

        $this->actingAs($user)
            ->get(route('inmopro.dashboard'))
            ->assertOk();
    }
}
