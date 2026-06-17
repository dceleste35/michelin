<?php

use App\Http\Controllers\Api\TireHealthController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::prefix('tires')->group(function () {
    Route::get('/{userTireId}/health', [TireHealthController::class, 'show']);
});

Route::get('/users/{userId}/recommendation', [RecommendationController::class, 'show']);