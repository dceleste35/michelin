<?php

use App\Http\Controllers\Api\TireHealthController;
use Illuminate\Support\Facades\Route;

// Idéalement, ces routes seront protégées par un middleware d'authentification (ex: auth:sanctum)
Route::prefix('tires')->group(function () {
    Route::get('/{userTireId}/health', [TireHealthController::class, 'show']);
});