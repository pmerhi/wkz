<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Status-Daten der Altkennzeichen-Infografik (je Kreis/KGS + Kürzel),
        // inkl. abgelehnter und beantragter Kennzeichen. Quelle: alte Grafik, in unsere DB übernommen.
        Schema::create('altkennzeichen_status', function (Blueprint $table) {
            $table->id();
            $table->string('kgs', 8)->index();      // Kreis-/Gemeindeschlüssel (kann auch Sonderkürzel wie BN sein)
            $table->string('kreisname')->nullable();
            $table->string('bundesland')->nullable();
            $table->string('kuerzel', 8);
            $table->unsignedTinyInteger('status')->default(0); // 0-12 (siehe infomap.js-Legende)
            $table->boolean('in_klammern')->default(false);
            $table->timestamps();

            $table->unique(['kgs', 'kuerzel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('altkennzeichen_status');
    }
};
