<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratgeber_artikel', function (Blueprint $table) {
            $table->id();
            $table->string('titel');
            $table->string('slug')->unique();
            $table->foreignId('kategorie_id')->nullable()
                  ->constrained('kategorien')->nullOnDelete();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->date('stand_datum')->nullable();   // Rechtsstand
            $table->text('quelle')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratgeber_artikel');
    }
};
