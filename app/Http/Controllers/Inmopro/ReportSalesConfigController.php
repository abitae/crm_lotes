<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\UpdateReportSalesConfigRequest;
use App\Models\Inmopro\ReportSalesConfig;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportSalesConfigController extends Controller
{
    public function edit(): Response
    {
        $config = ReportSalesConfig::current();

        return Inertia::render('inmopro/report-settings', [
            'config' => [
                'general_sales_goal' => (float) $config->general_sales_goal,
            ],
        ]);
    }

    public function update(UpdateReportSalesConfigRequest $request): RedirectResponse
    {
        ReportSalesConfig::current()->update($request->validated());

        return redirect()->route('inmopro.report-settings.edit')
            ->with('success', 'Meta general de reportes actualizada.');
    }
}
