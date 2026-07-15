<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ortbilder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gemeinde_id')->constrained('gemeinden')->cascadeOnDelete();

            // Rolle im Auswahl-Workflow: kandidat = recherchiert, hero/footer = ausgewählt.
            $table->string('rolle', 16)->default('kandidat');   // kandidat, hero, footer, abgelehnt

            // Lokaler Pfad unter public/ (gesetzt nach Download), sonst wird external_url genutzt.
            $table->string('src', 512)->nullable();

            // Flickr-Quelle
            $table->string('flickr_id', 32)->nullable();        // Dedup
            $table->string('external_url', 512)->nullable();     // Direktlink größte Auflösung
            $table->string('thumb_url', 512)->nullable();        // Vorschau fürs Admin
            $table->string('quelle', 512)->nullable();           // Flickr-Fotoseite

            // Attribution (TASL)
            $table->string('titel', 512)->nullable();
            $table->string('autor', 255)->nullable();
            $table->string('autor_url', 512)->nullable();
            $table->string('lizenz', 48)->nullable();            // z. B. "CC BY 2.0", "CC0"
            $table->string('lizenz_url', 255)->nullable();
            $table->boolean('bearbeitet')->default(false);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('wahrzeichen', 128)->nullable();      // Such-/Landmark-Begriff
            $table->unsignedSmallInteger('sort')->default(0);

            $table->timestamps();

            $table->index(['gemeinde_id', 'rolle']);
            $table->unique(['gemeinde_id', 'flickr_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ortbilder');
    }
};
