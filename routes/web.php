<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    // return Inertia::render('welcome'); // Original welcome page
    return Inertia::render('miner-dashboard'); // Redirect to miner dashboard for now
})->name('home');

// Keep original auth and dashboard routes if needed, or simplify for this task
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        // return Inertia::render('dashboard'); // Original dashboard
        return Inertia::render('miner-dashboard'); // Redirect to miner dashboard
    })->name('dashboard');
});

// Miner Dashboard Specific Routes (public for now, can be protected later)
Route::get('/miner-dashboard', [DashboardController::class, 'showMinerDashboard'])->name('miner.dashboard');
Route::get('/miner_log', [DashboardController::class, 'getMinerLog'])->name('miner.log.get');
Route::post('/send_miner_command', [DashboardController::class, 'sendMinerCommand'])->name('miner.command.send');
Route::post('/clear_miner_log', [DashboardController::class, 'clearMinerLog'])->name('miner.log.clear');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
