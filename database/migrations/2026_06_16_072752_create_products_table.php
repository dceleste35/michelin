<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('global_id')->nullable()->unique();
            $table->string('web_range_name');
            $table->string('segment')->nullable()->index();

            // Dimensions (ETRTO)
            $table->unsignedSmallInteger('width_etrto')->nullable();
            $table->unsignedSmallInteger('diameter_etrto')->nullable();
            $table->unsignedSmallInteger('tpi')->nullable();

            // Pression
            $table->decimal('min_pressure_bar', 4, 2)->nullable();
            $table->decimal('max_pressure_bar', 4, 2)->nullable();

            // Technologies
            $table->string('rubber_tech')->nullable();
            $table->string('casing_tech')->nullable();
            $table->string('reinforcement_tech')->nullable();
            $table->string('ebike_tech')->nullable();

            // Utilisation
            $table->jsonb('terrain_types')->nullable();
            $table->string('use')->nullable();

            // Performance / commerce
            $table->unsignedInteger('expected_life_km')->nullable();
            $table->decimal('rolling_resistance_watts', 5, 2)->nullable();
            $table->unsignedSmallInteger('weight_g')->nullable();
            $table->string('ean_code')->nullable()->index();
            $table->decimal('price_eur', 8, 2)->nullable();
            $table->text('image_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
