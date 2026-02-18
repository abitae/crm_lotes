<?php

use App\Http\Controllers\Inmopro\AdvisorController;
use App\Http\Controllers\Inmopro\AdvisorLevelController;
use App\Http\Controllers\Inmopro\ClientController;
use App\Http\Controllers\Inmopro\CommissionController;
use App\Http\Controllers\Inmopro\CommissionStatusController;
use App\Http\Controllers\Inmopro\DashboardController;
use App\Http\Controllers\Inmopro\FinancialController;
use App\Http\Controllers\Inmopro\LotController;
use App\Http\Controllers\Inmopro\LotStatusController;
use App\Http\Controllers\Inmopro\ProjectController;
use App\Http\Controllers\Inmopro\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inmopro')->name('inmopro.')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('projects/excel-template', [ProjectController::class, 'excelTemplate'])->name('projects.excel-template');
    Route::post('projects/import-from-excel', [ProjectController::class, 'importFromExcel'])->name('projects.import-from-excel');
    Route::resource('projects', ProjectController::class);
    Route::resource('lots', LotController::class);
    Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class);
    Route::get('advisors/search', [AdvisorController::class, 'search'])->name('advisors.search');
    Route::resource('advisors', AdvisorController::class)->except(['destroy']);
    Route::resource('lot-statuses', LotStatusController::class)->parameters(['lot-statuses' => 'lot_status']);
    Route::resource('commission-statuses', CommissionStatusController::class)->parameters(['commission-statuses' => 'commission_status']);
    Route::resource('advisor-levels', AdvisorLevelController::class)->parameters(['advisor-levels' => 'advisor_level']);
    Route::get('financial', [FinancialController::class, 'index'])->name('financial.index');
    Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::post('commissions/{commission}/mark-as-paid', [CommissionController::class, 'markAsPaid'])->name('commissions.mark-as-paid');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});
