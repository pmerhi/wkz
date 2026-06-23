<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Konsolidierter „Golden Record" je physischer Zulassungsstelle (AGS-verknüpft).
        // INTERN — Produktnutzung erst nach anwaltlicher Freigabe.
        Schema::create('konsolidierte_stelle', function (Blueprint $table) {
            $table->id();
            $table->string('identitaet')->unique();   // plz|norm-strasse
            $table->string('name')->nullable();
            $table->string('strasse')->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('ort')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('oeffnungszeiten')->nullable();
            $table->foreignId('gemeinde_id')->nullable()->constrained('gemeinden')->nullOnDelete();
            $table->foreignId('kreis_id')->nullable()->constrained('kreise')->nullOnDelete();
            $table->json('quellen')->nullable();        // Liste der Wettbewerber-Namen
            $table->unsignedTinyInteger('quellen_anzahl')->default(0);
            $table->timestamps();
            $table->index('plz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konsolidierte_stelle');
    }
};
