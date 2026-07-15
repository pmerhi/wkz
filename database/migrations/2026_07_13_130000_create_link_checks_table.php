<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ergebnisse der externen Link-Prüfung (Command `links:check`, Admin „Link-Check").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_checks', function (Blueprint $table) {
            $table->id();
            $table->char('url_hash', 40)->unique();          // sha1(url) – stabiler Schlüssel
            $table->string('url', 1024);
            $table->text('quellen')->nullable();             // wo der Link gefunden wurde
            $table->unsignedSmallInteger('status')->nullable();
            $table->boolean('ok')->default(false);
            $table->string('redirect_to', 1024)->nullable();
            $table->string('fehler', 512)->nullable();
            $table->timestamp('geprueft_at')->nullable();
            $table->timestamps();

            $table->index('ok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_checks');
    }
};
