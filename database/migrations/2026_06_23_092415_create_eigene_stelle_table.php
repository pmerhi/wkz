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
        Schema::create('eigene_stelle', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('ort_slug')->index();
            $table->string('name')->nullable();
            $table->string('strasse')->nullable();
            $table->string('plz', 5)->nullable()->index();
            $table->string('ort')->nullable();
            $table->string('telefon')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->json('oeffnungszeiten')->nullable();
            $table->string('termin_url', 600)->nullable();
            $table->string('termin_system')->nullable();   // frontdesksuite, tevis, etermin …
            $table->string('funnel_url', 600)->nullable(); // wunsch.kennzeichenbox.de …
            $table->string('kuerzel')->nullable();         // symbol= aus dem Funnel-Link
            $table->string('zulassungsbezirk')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eigene_stelle');
    }
};
