<?php

use App\Http\Controllers\StravaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// "Connect with Strava" — simulated for the prototype (signs in as the seeded hero, Marc).
Route::get('auth/strava/connect', [StravaController::class, 'connect'])->name('strava.connect');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
