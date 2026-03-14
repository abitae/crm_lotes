<?php

use App\Http\Controllers\Api\v1\Cazador\AttentionTicketController;
use App\Http\Controllers\Api\v1\Cazador\AuthController;
use App\Http\Controllers\Api\v1\Cazador\ClientController;
use App\Http\Controllers\Api\v1\Cazador\LotController;
use App\Http\Controllers\Api\v1\Cazador\PreReservationController;
use App\Http\Controllers\Api\v1\Cazador\ProfileController;
use App\Http\Controllers\Api\v1\Cazador\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/cazador')->name('api.v1.cazador.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('advisor.api')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [ProfileController::class, 'show'])->name('me.show');
        Route::put('me', [ProfileController::class, 'update'])->name('me.update');
        Route::put('me/pin', [ProfileController::class, 'updatePin'])->name('me.pin.update');

        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::get('attention-tickets', [AttentionTicketController::class, 'index'])->name('attention-tickets.index');
        Route::post('attention-tickets', [AttentionTicketController::class, 'store'])->name('attention-tickets.store');
        Route::post('attention-tickets/{attentionTicket}/cancel', [AttentionTicketController::class, 'cancel'])->name('attention-tickets.cancel');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('lots', [LotController::class, 'index'])->name('lots.index');
        Route::get('lots/{lot}', [LotController::class, 'show'])->name('lots.show');
        Route::post('lots/{lot}/pre-reservations', [PreReservationController::class, 'store'])->name('lots.pre-reservations.store');
    });
});
