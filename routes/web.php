<?php

use App\Http\Controllers\StravaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// « Connect with Strava » — simulé pour le prototype (connecte le héros de démo, Marc).
Route::get('auth/strava/connect', [StravaController::class, 'connect'])->name('strava.connect');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('profile', 'pages::profile')->name('profile');
    Route::livewire('activities', 'pages::activities')->name('activities');
    Route::livewire('tires', 'pages::tires')->name('tires');
    Route::livewire('tires/{userTire}', 'pages::tire-detail')->name('tires.show');
    Route::livewire('alerts', 'pages::alerts')->name('alerts');
});

require __DIR__.'/settings.php';
