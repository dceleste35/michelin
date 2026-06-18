<?php

use App\Http\Controllers\StravaController;
use App\Support\QrCode;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Porte QR à fournir au client : sur desktop un QR « scanne avec ton téléphone »,
// sur mobile un accès direct à l'app. Le QR encode l'URL de l'app.
Route::get('/qr', fn () => view('qr', [
    'appUrl' => url('/'),
    'qrSvg' => QrCode::svg(url('/')),
]))->name('qr');

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
