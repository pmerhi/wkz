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
        Schema::create('ratgeber_thema', function (Blueprint $table) {
            $table->id();
            $table->string('kategorie')->index();          // Pillar-Cluster
            $table->string('slug')->unique();
            $table->string('titel');
            $table->string('focus_keyword');
            $table->json('keywords')->nullable();          // Sekundär-Keywords
            $table->string('such_intention')->default('informational'); // informational|kommerziell|transaktional
            $table->string('volumen')->default('mittel');  // hoch|mittel|niedrig (Schätzung)
            $table->string('funnel_wert')->default('mittel'); // hoch|mittel|niedrig (Nähe zum Reservierungs-Funnel)
            $table->unsignedSmallInteger('prioritaet')->default(0); // berechnet, höher = wichtiger
            $table->unsignedSmallInteger('wort_ziel')->default(1000);
            $table->json('interne_links')->nullable();     // Slugs verwandter Themen
            $table->boolean('vorhanden')->default(false);  // existiert bereits (eigene/alte Seite)
            $table->string('status')->default('geplant')->index(); // geplant|in_arbeit|fertig
            $table->text('notiz')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratgeber_thema');
    }
};
