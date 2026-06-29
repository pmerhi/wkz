<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staging für von offiziellen Behördenseiten extrahierte Öffnungszeiten.
        // Wird geprüft und erst nach Freigabe in zulassungsstellen.oeffnungszeiten übernommen.
        Schema::create('offizielle_oeffnungszeiten', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zulassungsstelle_id')->unique()->constrained('zulassungsstellen')->cascadeOnDelete();
            $table->string('quelle_url', 1024);
            $table->json('oeffnungszeiten')->nullable();          // [{day,label,opens,closes}]
            $table->string('status')->default('offen');           // ok | keine_zeiten | fehler | unsicher
            $table->text('hinweis')->nullable();                  // Anmerkung der Extraktion
            $table->text('roh_auszug')->nullable();               // Textstelle, aus der extrahiert wurde (Audit)
            $table->string('modell')->nullable();
            $table->boolean('uebernommen')->default(false);       // in Live-Daten übernommen?
            $table->timestamp('extrahiert_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offizielle_oeffnungszeiten');
    }
};
