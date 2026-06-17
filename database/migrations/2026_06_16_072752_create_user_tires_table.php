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
        Schema::create('user_tires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->enum('position', ['FRONT', 'REAR']);
            $table->date('mounted_at')->nullable();
            $table->unsignedInteger('mounted_odometer_km')->nullable();
            $table->decimal('wear_percent', 5, 2)->nullable(); // cache (0-100)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('product_id'); // FK lookups (Product → mounted tires)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tires');
    }
};
