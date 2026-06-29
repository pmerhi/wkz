<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            // Letzter HTTP-Status der Öffnungszeiten-URL (0 = nicht erreichbar/DNS).
            $table->smallInteger('oeffnungszeiten_url_status')->nullable()->after('oeffnungszeiten_url_quelle');
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropColumn('oeffnungszeiten_url_status');
        });
    }
};
