<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicDateroClientRegistrationRequest;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Datero;
use App\Services\Inmopro\RegisterClientForDateroAction;
use App\Support\DateroRegistrationUrl;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicDateroClientRegistrationController extends Controller
{
    public function show(Request $request, string $token): InertiaResponse
    {
        $datero = $this->resolvePublicDatero($token);
        if ($datero === null) {
            return Inertia::render('public/datero-invite-invalid');
        }

        $datero->loadMissing('assignedAdvisor:id,name');

        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'department']);

        return Inertia::render('public/datero-client-register', [
            'token' => $token,
            'capturerName' => $datero->name,
            'advisorName' => $datero->assignedAdvisor?->name,
            'cities' => $cities,
        ]);
    }

    public function store(
        PublicDateroClientRegistrationRequest $request,
        string $token,
        RegisterClientForDateroAction $registerClient,
    ): RedirectResponse {
        $datero = $this->resolvePublicDatero($token);
        if ($datero === null) {
            return redirect()->route('public.datero-registration.show', ['token' => $token])
                ->with('error', 'Este enlace ya no está disponible.');
        }

        $registerClient->execute($datero, $request->validated());

        return redirect()->route('public.datero-registration.show', ['token' => $token])
            ->with('success', 'Registro completado. Gracias; su asesor se pondrá en contacto.');
    }

    public function qrPng(string $token): Response
    {
        $datero = $this->resolvePublicDatero($token);
        if ($datero === null) {
            return response('', 404);
        }

        $targetUrl = DateroRegistrationUrl::forDatero($datero);
        if ($targetUrl === null || $targetUrl === '') {
            return response('', 404);
        }

        $qrCode = new QrCode(
            data: $targetUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(15, 23, 42),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter;
        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    private function resolvePublicDatero(string $token): ?Datero
    {
        $datero = Datero::query()
            ->where('invite_token', $token)
            ->where('is_active', true)
            ->with('assignedAdvisor:id,name,is_active')
            ->first();

        if ($datero === null) {
            return null;
        }

        $advisor = $datero->assignedAdvisor;
        if ($advisor === null || ! $advisor->is_active) {
            return null;
        }

        return $datero;
    }
}
