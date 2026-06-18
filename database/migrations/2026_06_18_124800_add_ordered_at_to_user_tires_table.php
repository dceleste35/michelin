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
            // Pneu en fin de vie dont le remplacement a été commandé (acquitte l'alerte / la cloche).
            $table->timestamp('ordered_at')->nullable()->after('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tires', function (Blueprint $table) {
            $table->dropColumn('ordered_at');
        });
    }
};
