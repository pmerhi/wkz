<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wettbewerber', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('url');
            $table->string('typ')->nullable();          // funnel|verzeichnis|ratgeber|mix|shop
            $table->string('betreiber')->nullable();    // Impressum/Firma (für Dedup)
            $table->unsignedSmallInteger('rang')->nullable();
            $table->text('dedup_hinweis')->nullable();  // Distinktheit / bekannte Spiegel
            $table->text('notizen')->nullable();        // spätere Analyse-Notizen
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wettbewerber');
    }
};
