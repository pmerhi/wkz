<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kreise / kreisfreie Städte (5-stelliger AGS)
        Schema::create('kreise', function (Blueprint $table) {
            $table->id();
            $table->string('ags', 5)->unique();
            $table->string('name')->nullable();
            $table->foreignId('bundesland_id')->nullable()
                  ->constrained('bundeslaender')->nullOnDelete();
            $table->timestamps();
        });

        // Gemeinden (8-stelliger AGS)
        Schema::create('gemeinden', function (Blueprint $table) {
            $table->id();
            $table->string('ags', 8)->unique();
            $table->string('name');
            $table->foreignId('kreis_id')->nullable()
                  ->constrained('kreise')->nullOnDelete();
            $table->foreignId('bundesland_id')->nullable()
                  ->constrained('bundeslaender')->nullOnDelete();
            $table->timestamps();
            $table->index('name');
        });

        // Zulassungsstellen an die AGS-Struktur hängen
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->foreignId('gemeinde_id')->nullable()->after('bundesland_id')
                  ->constrained('gemeinden')->nullOnDelete();
            $table->foreignId('kreis_id')->nullable()->after('gemeinde_id')
                  ->constrained('kreise')->nullOnDelete();
        });

        // Kürzel an Kreise binden (n:m — ein Kürzel kann mehrere Kreise abdecken)
        Schema::create('kennzeichen_kuerzel_kreis', function (Blueprint $table) {
            $table->foreignId('kennzeichen_kuerzel_id')
                  ->constrained('kennzeichen_kuerzel')->cascadeOnDelete();
            $table->foreignId('kreis_id')
                  ->constrained('kreise')->cascadeOnDelete();
            $table->primary(['kennzeichen_kuerzel_id', 'kreis_id'], 'kkz_kreis_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kennzeichen_kuerzel_kreis');
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gemeinde_id');
            $table->dropConstrainedForeignId('kreis_id');
        });
        Schema::dropIfExists('gemeinden');
        Schema::dropIfExists('kreise');
    }
};
