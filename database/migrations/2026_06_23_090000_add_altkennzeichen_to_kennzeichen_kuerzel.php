<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Altkennzeichen-Markierung: Kennzeichen, die auslaufend waren und im Rahmen der
 * Kennzeichenliberalisierung (FZV, ab 01.11.2012) wieder eingeführt wurden.
 * `historische_stadt` = ursprüngliche Bedeutung (z. B. BCH → Buchen),
 * `bedeutung_quelle` = Datenherkunft für die Nachvollziehbarkeit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kennzeichen_kuerzel', function (Blueprint $table) {
            $table->boolean('ist_altkennzeichen')->default(false)->index()->after('bedeutung');
            $table->string('historische_stadt')->nullable()->after('ist_altkennzeichen');
            $table->string('bedeutung_quelle')->nullable()->after('historische_stadt');
        });
    }

    public function down(): void
    {
        Schema::table('kennzeichen_kuerzel', function (Blueprint $table) {
            $table->dropColumn(['ist_altkennzeichen', 'historische_stadt', 'bedeutung_quelle']);
        });
    }
};
