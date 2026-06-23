<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kind-Stellen (Außenstellen) zeigen auf das Primär-Amt des Ortes/Bezirks.
 * Nur Primär-Stellen (parent_id NULL) haben eine eigene Seite/URL; Kinder werden
 * auf das Primär-Amt weitergeleitet und auf dessen Detailseite gelistet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('zulassungsstellen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
