<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('strava_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('sport_type');

            // Métriques Strava
            $table->unsignedInteger('distance_m');
            $table->unsignedInteger('moving_time_s');
            $table->decimal('average_speed_ms', 6, 3);
            $table->unsignedInteger('total_elevation_gain_m')->default(0);
            $table->unsignedSmallInteger('average_watts')->nullable();
            $table->unsignedSmallInteger('average_cadence')->nullable();

            // Surface dérivée (SCORE) — voir ProfileInferenceService
            $table->enum('surface_derived', ['ASPHALT', 'HARDPACKED', 'MIXED', 'SOFT', 'MUD'])->nullable();

            $table->timestamp('start_date')->index();
            $table->jsonb('raw_json');

            $table->timestamps();

            // Idempotence d'import : une activité Strava par utilisateur
            $table->unique(['user_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strava_activities');
    }
};
