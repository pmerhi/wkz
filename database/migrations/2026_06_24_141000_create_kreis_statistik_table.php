<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kreis_statistik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kreis_id')->unique()->constrained('kreise')->cascadeOnDelete();
            $table->unsignedInteger('einwohner')->nullable();
            $table->decimal('flaeche_km2', 10, 2)->nullable();
            $table->unsignedInteger('kfz_bestand')->nullable();      // alle Kraftfahrzeuge
            $table->unsignedInteger('pkw_bestand')->nullable();      // nur Pkw
            $table->decimal('pkw_dichte', 6, 1)->nullable();         // Pkw je 1.000 Einwohner
            $table->smallInteger('stand_jahr')->nullable();
            $table->string('quelle')->nullable();                    // z. B. "KBA / Destatis 2024"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kreis_statistik');
    }
};
