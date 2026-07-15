<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quelle von Flickr-API auf Openverse umgestellt (offene API ohne kommerzielle
 * Nutzungs-Einschränkung, aggregiert u. a. Flickr + Wikimedia). Die zuvor
 * flickr-spezifische Spalte wird generisch, plus Herkunfts-Provider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ortbilder', function (Blueprint $table) {
            $table->renameColumn('flickr_id', 'extern_id');
        });
        Schema::table('ortbilder', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->after('quelle'); // wikimedia, flickr, …
        });
    }

    public function down(): void
    {
        Schema::table('ortbilder', function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->renameColumn('extern_id', 'flickr_id');
        });
    }
};
