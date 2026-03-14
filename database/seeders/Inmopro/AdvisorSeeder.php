<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\Team;
use Illuminate\Database\Seeder;

class AdvisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Team::query()->exists()) {
            $this->call(TeamSeeder::class);
        }

        $level4 = AdvisorLevel::where('code', 'NIVEL_4')->first();
        $level3 = AdvisorLevel::where('code', 'NIVEL_3')->first();
        $level2 = AdvisorLevel::where('code', 'NIVEL_2')->first();
        $level1 = AdvisorLevel::where('code', 'NIVEL_1')->first();
        $teams = Team::orderBy('sort_order')->get()->values();

        $level4Advisors = [];
        for ($i = 1; $i <= 5; $i++) {
            $level4Advisors[] = Advisor::updateOrCreate(
                ['email' => "director{$i}@inmopro.com"],
                [
                    'name' => "DIRECTOR EJECUTIVO {$i}",
                    'phone' => '90040000'.$i,
                    'username' => "director{$i}",
                    'pin' => '123456',
                    'is_active' => true,
                    'team_id' => $teams[($i - 1) % $teams->count()]->id,
                    'advisor_level_id' => $level4->id,
                    'superior_id' => null,
                    'personal_quota' => 1000000,
                ]
            );
        }

        $level3Advisors = [];
        for ($i = 1; $i <= 30; $i++) {
            $superior = $level4Advisors[$i % 5];
            $level3Advisors[] = Advisor::updateOrCreate(
                ['email' => "gerente{$i}@inmopro.com"],
                [
                    'name' => "GERENTE COMERCIAL {$i}",
                    'phone' => '9003000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'username' => "gerente{$i}",
                    'pin' => '123456',
                    'is_active' => true,
                    'team_id' => $teams[($i - 1) % $teams->count()]->id,
                    'advisor_level_id' => $level3->id,
                    'superior_id' => $superior->id,
                    'personal_quota' => 500000,
                ]
            );
        }

        $level2Advisors = [];
        for ($i = 1; $i <= 50; $i++) {
            $superior = $level3Advisors[$i % 30];
            $level2Advisors[] = Advisor::updateOrCreate(
                ['email' => "senior{$i}@inmopro.com"],
                [
                    'name' => "ASESOR SENIOR {$i}",
                    'phone' => '9002000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'username' => "senior{$i}",
                    'pin' => '123456',
                    'is_active' => true,
                    'team_id' => $teams[($i - 1) % $teams->count()]->id,
                    'advisor_level_id' => $level2->id,
                    'superior_id' => $superior->id,
                    'personal_quota' => 250000,
                ]
            );
        }

        for ($i = 1; $i <= 60; $i++) {
            $superior = $level2Advisors[$i % 50];
            Advisor::updateOrCreate(
                ['email' => "asesor{$i}@inmopro.com"],
                [
                    'name' => "ASESOR NIVEL 1 - {$i}",
                    'phone' => '9001000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'username' => "asesor{$i}",
                    'pin' => '123456',
                    'is_active' => true,
                    'team_id' => $teams[($i - 1) % $teams->count()]->id,
                    'advisor_level_id' => $level1->id,
                    'superior_id' => $superior->id,
                    'personal_quota' => 120000,
                ]
            );
        }
    }
}
