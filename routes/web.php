<?php

use App\Http\Controllers\Inmopro\DashboardController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\PublicDateroClientRegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::get('/registro-datero/{token}/qr.png', [PublicDateroClientRegistrationController::class, 'qrPng'])
    ->middleware('throttle:datero-public-qr')
    ->name('public.datero-registration.qr');
Route::get('/registro-datero/{token}', [PublicDateroClientRegistrationController::class, 'show'])
    ->name('public.datero-registration.show');
Route::post('/registro-datero/{token}', [PublicDateroClientRegistrationController::class, 'store'])
    ->middleware('throttle:datero-public-register')
    ->name('public.datero-registration.store');

Route::get('/legal/terminos', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/legal/privacidad', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/inmopro.php';
require __DIR__.'/settings.php';
