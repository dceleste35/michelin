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
        Schema::table('user_tires', function (Blueprint $table) {
            // Km « de départ » du pneu (usage hors sorties trackées / levier démo).
            // Usure = (baseline_wear_km + km des sorties associées) / durée de vie.
            $table->decimal('baseline_wear_km', 10, 2)->default(0)->after('wear_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tires', function (Blueprint $table) {
            $table->dropColumn('baseline_wear_km');
        });
    }
};
