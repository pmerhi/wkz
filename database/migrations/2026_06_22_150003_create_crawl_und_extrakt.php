<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seiten-Archiv (Kopien der Wettbewerber-Seiten, für interne Analyse)
        Schema::create('crawl_seite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wettbewerber_id')->constrained('wettbewerber')->cascadeOnDelete();
            $table->text('url');
            $table->char('url_hash', 40);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('content_type')->nullable();
            $table->string('titel')->nullable();
            $table->longText('html')->nullable();
            $table->longText('text')->nullable();
            $table->char('inhalt_hash', 40)->nullable();
            $table->timestamp('abgerufen_am')->nullable();
            $table->timestamps();
            $table->unique(['wettbewerber_id', 'url_hash'], 'crawl_seite_unique');
        });

        // Extrakt: Zulassungsstellen aus Wettbewerber-Seiten (AGS-verknüpft, intern)
        Schema::create('extrakt_zulassungsstelle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wettbewerber_id')->constrained('wettbewerber')->cascadeOnDelete();
            $table->foreignId('crawl_seite_id')->nullable()->constrained('crawl_seite')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('strasse')->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('ort')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('termin_url')->nullable();
            $table->json('oeffnungszeiten')->nullable();
            $table->foreignId('gemeinde_id')->nullable()->constrained('gemeinden')->nullOnDelete();
            $table->foreignId('kreis_id')->nullable()->constrained('kreise')->nullOnDelete();
            $table->string('quelle_url')->nullable();
            $table->json('roh')->nullable();
            $table->timestamps();
        });

        // Extrakt: Kennzeichen-Kürzel aus Wettbewerber-Seiten (AGS-verknüpft)
        Schema::create('extrakt_kuerzel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wettbewerber_id')->constrained('wettbewerber')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('bedeutung')->nullable();
            $table->foreignId('kreis_id')->nullable()->constrained('kreise')->nullOnDelete();
            $table->string('quelle_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extrakt_kuerzel');
        Schema::dropIfExists('extrakt_zulassungsstelle');
        Schema::dropIfExists('crawl_seite');
    }
};
