<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zulassungsstellen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('traeger')->nullable();          // Kreis/Stadt
            $table->string('strasse')->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('ort')->nullable();
            $table->foreignId('bundesland_id')->nullable()
                  ->constrained('bundeslaender')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('termin_url')->nullable();        // Online-Terminvergabe
            $table->json('oeffnungszeiten')->nullable();      // strukturiert
            $table->string('quelle')->nullable();             // Datenherkunft
            $table->timestamp('last_imported_at')->nullable();// Stand
            $table->timestamps();

            $table->index('ort');
            $table->index('plz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zulassungsstellen');
    }
};
