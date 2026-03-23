<?php

use App\Http\Controllers\Inmopro\DashboardController;
use App\Http\Controllers\LegalDocumentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::get('/legal/terminos', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/legal/privacidad', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/inmopro.php';
require __DIR__.'/settings.php';
