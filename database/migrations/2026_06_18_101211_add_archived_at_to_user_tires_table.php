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
            // Pneu rangé hors de la collection courante (conservé pour l'historique), restaurable.
            $table->timestamp('archived_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tires', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
