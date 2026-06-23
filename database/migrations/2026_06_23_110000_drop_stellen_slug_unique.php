<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Globalen Unique-Index auf zulassungsstellen.slug entfernen. Slugs sind künftig
 * nur noch je Bundesland eindeutig (URL /zulassungsstelle/{land}/{ort}).
 * Die zusammengesetzte Eindeutigkeit folgt nach dem Slug-Rebuild.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropUnique('zulassungsstellen_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
