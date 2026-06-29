<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alte /wunschkennzeichen/{ort}/-Slugs, die nicht 1:1 auf einen heutigen Gemeinde-Slug
 * passen (Region-Suffixe, Ortsteile, Stelle-Slugs). Jeder Alias leitet 301 auf ein Ziel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ort_aliasse', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();            // alter Ort-Slug
            $table->string('ziel');                      // kanonischer Pfad (z.B. /wunschkennzeichen/amberg)
            $table->string('quelle')->nullable();        // wie gematcht (suffix, prefix, stelle, manuell)
            $table->boolean('geprueft')->default(false); // Region-Konsistenz bestätigt?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ort_aliasse');
    }
};
