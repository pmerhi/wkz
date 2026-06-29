<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            // Offizielle Seite, auf der die Öffnungszeiten stehen (zur Überwachung).
            $table->string('oeffnungszeiten_url', 1024)->nullable()->after('termin_url');
            // Woher diese URL stammt (z.B. "website", "kennzeichenking.de", "manuell").
            $table->string('oeffnungszeiten_url_quelle')->nullable()->after('oeffnungszeiten_url');
            // Fingerprint des öffnungszeiten-relevanten Inhalts (für Änderungserkennung).
            $table->string('oeffnungszeiten_hash', 64)->nullable()->after('oeffnungszeiten_url_quelle');
            // Zeitpunkt der letzten Prüfung.
            $table->timestamp('oeffnungszeiten_geprueft_at')->nullable()->after('oeffnungszeiten_hash');
            // Flag: seit der letzten bestätigten Übernahme hat sich die Seite geändert.
            $table->boolean('oeffnungszeiten_geaendert')->default(false)->after('oeffnungszeiten_geprueft_at');
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropColumn([
                'oeffnungszeiten_url', 'oeffnungszeiten_url_quelle', 'oeffnungszeiten_hash',
                'oeffnungszeiten_geprueft_at', 'oeffnungszeiten_geaendert',
            ]);
        });
    }
};
