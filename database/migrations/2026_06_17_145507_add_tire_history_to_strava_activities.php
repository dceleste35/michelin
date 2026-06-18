<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lie chaque sortie aux pneus avant/arrière montés au moment de la course, afin de
     * conserver l'historique même quand le cycliste change de pneus entre deux sorties.
     * FK ajoutées ici (et non dans la migration d'origine) car `user_tires` est créée après.
     */
    public function up(): void
    {
        Schema::table('strava_activities', function (Blueprint $table) {
            $table->foreignId('front_tire_id')->nullable()->after('gear_id')->constrained('user_tires')->nullOnDelete();
            $table->foreignId('rear_tire_id')->nullable()->after('front_tire_id')->constrained('user_tires')->nullOnDelete();
            $table->boolean('tires_confirmed')->default(false)->after('rear_tire_id'); // false = à vérifier par le cycliste
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::table('strava_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('front_tire_id');
            $table->dropConstrainedForeignId('rear_tire_id');
            $table->dropColumn('tires_confirmed');
        });
    }
};
