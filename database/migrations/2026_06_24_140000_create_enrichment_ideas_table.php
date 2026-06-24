<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrichment_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('titel');
            $table->string('kategorie', 64)->nullable();        // Daten, Lokal, Interaktiv, UGC, Wettbewerb …
            $table->text('beschreibung')->nullable();
            $table->text('umsetzung')->nullable();              // konkreter Umsetzungsvorschlag
            $table->string('quelle', 512)->nullable();          // Fund-URL / Beleg
            $table->string('wettbewerber')->nullable();         // wer macht das bereits

            $table->unsignedTinyInteger('seo_wert')->default(3);   // 1–5
            $table->unsignedTinyInteger('relevanz')->default(3);   // 1–5 (Themenbezug Kfz/lokal)
            $table->unsignedTinyInteger('aufwand')->default(3);    // 1–5
            $table->decimal('score', 5, 2)->default(0);            // seo_wert*relevanz/aufwand

            $table->string('status', 24)->default('neu');          // neu, geprueft, umsetzen, abgelehnt, umgesetzt
            $table->text('notiz')->nullable();                     // Kuratierungs-Notiz
            $table->string('quelle_lauf', 64)->nullable();         // Recherche-Lauf/Agent
            $table->string('fingerprint', 64)->nullable();         // Dedup-Hash
            $table->timestamps();

            $table->index('status');
            $table->index('score');
            $table->unique('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrichment_ideas');
    }
};
