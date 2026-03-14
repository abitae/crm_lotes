<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Illuminate\Database\Seeder;

class LotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->first();
        $statusTransferido = LotStatus::where('code', 'TRANSFERIDO')->first();

        $advisors = Advisor::all()->keyBy('id');
        $clients = Client::all();

        Project::with('lots')->get()->each(function (Project $project) use ($statusLibre, $statusReservado, $statusTransferido, $advisors, $clients): void {
            foreach ($project->blocks as $block) {
                $count = $project->name === 'Residencial Los Olivos' ? 20 : 15;
                for ($num = 1; $num <= $count; $num++) {
                    $rand = random_int(1, 10);
                    $status = $rand <= 6 ? $statusLibre : ($rand <= 8 ? $statusReservado : $statusTransferido);
                    $price = 25000 + (random_int(0, 15000));
                    $advance = $status->code !== 'LIBRE' ? (int) round($price * (0.1 + (random_int(0, 40) / 100))) : null;
                    $remainingBalance = $status->code !== 'LIBRE' ? $price - ($advance ?? 0) : null;
                    $contractDate = $status->code !== 'LIBRE' ? now()->subDays(random_int(0, 90))->format('Y-m-d') : null;

                    $advisorId = null;
                    $clientId = null;
                    $clientName = null;
                    $clientDni = null;
                    $paymentLimitDate = null;
                    $operationNumber = null;
                    $contractNumber = null;
                    if ($status->code !== 'LIBRE') {
                        $advisorId = $advisors->random()->id;
                        $client = $clients->random();
                        $clientId = $client->id;
                        $clientName = $client->name;
                        $clientDni = $client->dni;
                        $paymentLimitDate = now()->addDays(random_int(30, 120))->format('Y-m-d');
                        $operationNumber = 'OP-'.random_int(10000, 99999);
                        $contractNumber = 'CT-'.now()->format('Ymd').'-'.$num;
                    }

                    Lot::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'block' => $block,
                            'number' => $num,
                        ],
                        [
                            'area' => 105.00,
                            'price' => $price,
                            'lot_status_id' => $status->id,
                            'client_id' => $clientId,
                            'advisor_id' => $advisorId,
                            'client_name' => $clientName,
                            'client_dni' => $clientDni,
                            'advance' => $advance,
                            'remaining_balance' => $remainingBalance,
                            'payment_limit_date' => $paymentLimitDate,
                            'operation_number' => $operationNumber,
                            'contract_date' => $contractDate,
                            'contract_number' => $contractNumber,
                        ]
                    );
                }
            }
        });
    }
}
