<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zusammengesetzte Eindeutigkeit: ein Slug ist je Bundesland eindeutig
 * (URL /zulassungsstelle/{land}/{ort}). Nach dem Slug-Rebuild gefahrlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->unique(['bundesland_id', 'slug'], 'zst_land_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropUnique('zst_land_slug_unique');
        });
    }
};
