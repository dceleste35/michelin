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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // RideReady — profil rider (inféré par ProfileInferenceService, SCORE)
            $table->string('strava_athlete_id')->nullable()->unique();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->enum('segment', ['GRAVEL', 'ROAD', 'MTB', 'EBIKE_URBAN'])->nullable();
            $table->boolean('segment_overridden')->default(false);
            $table->enum('riding_style', ['ENDURANCE', 'AGGRESSIF'])->nullable();
            $table->timestamp('profile_confirmed_at')->nullable(); // validation de la valeur par défaut intelligente (une seule fois)

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
