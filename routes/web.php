<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpinWheelController;
use App\Http\Controllers\WalletSimulationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('', 302)->header('Location', '/spin-wheel'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::get('/spin-wheel', [SpinWheelController::class, 'index'])->name('spin-wheel.index');
Route::post('/spin-wheel/spin', [SpinWheelController::class, 'spin'])->name('spin-wheel.spin');
Route::post('/spin-wheel/claim/{history}', [SpinWheelController::class, 'claim'])->middleware('auth')->name('spin-wheel.claim');

Route::middleware('auth')->group(function () {
    Route::get('/wallet-simulation', [WalletSimulationController::class, 'index'])->name('wallet.simulation');
    Route::redirect('/dashboard', '/wallet-simulation')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
