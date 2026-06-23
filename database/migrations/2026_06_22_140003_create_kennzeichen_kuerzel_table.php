<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kennzeichen_kuerzel', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();   // Unterscheidungszeichen, z.B. B, M, K
            $table->string('slug')->unique();
            $table->string('bedeutung')->nullable();// Stadt/Kreis-Bezeichnung
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kennzeichen_kuerzel');
    }
};
