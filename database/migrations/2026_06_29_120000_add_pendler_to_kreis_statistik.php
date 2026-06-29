<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            // Pendlerdaten (Pendleratlas der statistischen Ämter) – Kreis-Ebene.
            $table->decimal('auspendler_quote', 5, 1)->nullable()->after('ladepunkte_stand');
            $table->decimal('einpendler_quote', 5, 1)->nullable()->after('auspendler_quote');
            $table->integer('pendler_saldo')->nullable()->after('einpendler_quote');
            $table->string('pendler_stand', 20)->nullable()->after('pendler_saldo');
        });
    }

    public function down(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            $table->dropColumn(['auspendler_quote', 'einpendler_quote', 'pendler_saldo', 'pendler_stand']);
        });
    }
};
