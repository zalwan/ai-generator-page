<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/sales-pages', [SalesPageController::class, 'index'])->name('sales-pages.index');
    Route::get('/sales-pages/create', [SalesPageController::class, 'create'])->name('sales-pages.create');
    Route::post('/sales-pages/suggest', [SalesPageController::class, 'suggest'])->name('sales-pages.suggest');
    Route::post('/sales-pages', [SalesPageController::class, 'store'])->name('sales-pages.store');
    Route::get('/sales-pages/{salesPage}', [SalesPageController::class, 'show'])->name('sales-pages.show');
    Route::get('/sales-pages/{salesPage}/preview', [SalesPageController::class, 'preview'])->name('sales-pages.preview');
    Route::delete('/sales-pages/{salesPage}', [SalesPageController::class, 'destroy'])->name('sales-pages.destroy');
});
