<?php

namespace App\Http\Controllers\Inmopro;

use App\Ai\Agents\LotFollowUpAssistant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\LotAiFollowUpSuggestionRequest;
use App\Models\Inmopro\Lot;
use Illuminate\Http\JsonResponse;

class LotAiFollowUpSuggestionController extends Controller
{
    public function __invoke(LotAiFollowUpSuggestionRequest $request, Lot $lot): JsonResponse
    {
        $lot->load(['project', 'status', 'client', 'advisor']);

        $lines = [
            'Datos del lote:',
            '- Proyecto: '.($lot->project?->name ?? 'N/D'),
            '- Manzana / número: '.($lot->block ?? '').' / '.($lot->number ?? ''),
            '- Estado: '.($lot->status?->name ?? 'N/D'),
            '- Precio: '.($lot->price !== null ? (string) $lot->price : 'N/D'),
            '- Saldo pendiente: '.($lot->remaining_balance !== null ? (string) $lot->remaining_balance : 'N/D'),
            '- Observaciones del lote: '.($lot->observations ? (string) $lot->observations : 'ninguna'),
        ];

        if ($lot->client) {
            $lines[] = 'Cliente: '.$lot->client->name;
            $lines[] = 'Teléfono cliente: '.($lot->client->phone ?? 'N/D');
        } else {
            $lines[] = 'Cliente (texto en lote): '.($lot->client_name ?? 'N/D');
        }

        if ($lot->advisor) {
            $lines[] = 'Asesor asignado: '.$lot->advisor->name;
        }

        $extra = $request->validated('extra_context');
        if (is_string($extra) && trim($extra) !== '') {
            $lines[] = 'Indicaciones adicionales del asesor: '.$extra;
        }

        $lines[] = 'Redacta una sugerencia corta de mensaje o llamada de seguimiento para este caso.';

        $prompt = implode("\n", $lines);

        $response = (new LotFollowUpAssistant)->prompt($prompt);

        return response()->json([
            'suggestion' => (string) $response,
        ]);
    }
}
