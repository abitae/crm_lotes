<?php

namespace Tests\Feature\Seeders;

use App\Models\Inmopro\AdvisorReminder;
use App\Models\Inmopro\AttentionTicket;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\Asesor1RemindersAndTicketsSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\FunctionalTestingSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Asesor1RemindersAndTicketsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_reminders_and_ticket_for_asesor1_and_is_idempotent(): void
    {
        $this->seed(TeamSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(AdvisorSeeder::class);
        $this->seed(AuthorizationSeeder::class);
        $this->seed(FunctionalTestingSeeder::class);

        $this->seed(Asesor1RemindersAndTicketsSeeder::class);
        $this->seed(Asesor1RemindersAndTicketsSeeder::class);

        $this->assertDatabaseHas('advisor_reminders', [
            'title' => Asesor1RemindersAndTicketsSeeder::REMINDER_TITLE,
        ]);
        $this->assertDatabaseHas('advisor_reminders', [
            'title' => Asesor1RemindersAndTicketsSeeder::REMINDER_TITLE_COMPLETED,
        ]);
        $this->assertDatabaseHas('attention_tickets', [
            'notes' => Asesor1RemindersAndTicketsSeeder::TICKET_NOTES,
            'status' => 'pendiente',
        ]);

        $this->assertSame(
            2,
            AdvisorReminder::query()
                ->whereIn('title', [
                    Asesor1RemindersAndTicketsSeeder::REMINDER_TITLE,
                    Asesor1RemindersAndTicketsSeeder::REMINDER_TITLE_COMPLETED,
                ])
                ->count()
        );
        $this->assertSame(
            1,
            AttentionTicket::query()
                ->where('notes', Asesor1RemindersAndTicketsSeeder::TICKET_NOTES)
                ->count()
        );
    }

    public function test_seeder_fails_without_propio_client_for_asesor1(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no tiene cliente PROPIO');

        $this->seed(TeamSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(AdvisorSeeder::class);

        $this->seed(Asesor1RemindersAndTicketsSeeder::class);
    }
}
