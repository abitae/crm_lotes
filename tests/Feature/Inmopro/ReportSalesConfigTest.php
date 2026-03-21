<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\ReportSalesConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSalesConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_authenticated_user_can_view_report_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.report-settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/report-settings')
                ->where('config.general_sales_goal', 0));
    }

    public function test_authenticated_user_can_update_general_sales_goal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('inmopro.report-settings.update'), [
                'general_sales_goal' => 125000.5,
            ])
            ->assertRedirect(route('inmopro.report-settings.edit'));

        $this->assertEquals(125000.5, (float) ReportSalesConfig::current()->general_sales_goal);
    }

    public function test_update_validates_general_sales_goal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('inmopro.report-settings.update'), [
                'general_sales_goal' => -1,
            ])
            ->assertSessionHasErrors('general_sales_goal');
    }
}
