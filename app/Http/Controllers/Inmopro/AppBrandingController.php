<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\UpdateAppBrandingRequest;
use App\Models\AppBranding;
use App\Support\AppBrandingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppBrandingController extends Controller
{
    public function edit(): Response
    {
        $branding = AppBranding::current();

        return Inertia::render('inmopro/branding', [
            'branding' => [
                'display_name' => $branding->display_name,
                'tagline' => $branding->tagline,
                'primary_color' => $branding->primary_color,
                'logo_url' => filled($branding->logo_path)
                    ? Storage::disk('public')->url($branding->logo_path)
                    : null,
                'favicon_url' => filled($branding->favicon_path)
                    ? Storage::disk('public')->url($branding->favicon_path)
                    : null,
            ],
        ]);
    }

    public function update(UpdateAppBrandingRequest $request): RedirectResponse
    {
        $branding = AppBranding::current();
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            if (filled($branding->logo_path)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $branding->logo_path = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if (filled($branding->logo_path)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $branding->logo_path = null;
        }

        if ($request->hasFile('favicon')) {
            if (filled($branding->favicon_path)) {
                Storage::disk('public')->delete($branding->favicon_path);
            }
            $branding->favicon_path = $request->file('favicon')->store('branding/favicons', 'public');
        } elseif ($request->boolean('remove_favicon')) {
            if (filled($branding->favicon_path)) {
                Storage::disk('public')->delete($branding->favicon_path);
            }
            $branding->favicon_path = null;
        }

        $branding->display_name = filled($validated['display_name'] ?? null)
            ? $validated['display_name']
            : null;

        $branding->tagline = filled($validated['tagline'] ?? null)
            ? $validated['tagline']
            : null;

        $branding->primary_color = filled($validated['primary_color'] ?? null)
            ? $validated['primary_color']
            : null;

        $branding->save();

        AppBrandingResolver::forgetCache();

        return redirect()->route('inmopro.branding.edit')
            ->with('success', 'Personalización actualizada.');
    }
}
