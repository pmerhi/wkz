<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner')->cascadeOnDelete();
            $table->string('name');
            $table->enum('typ', ['block', 'in_text'])->default('block');
            $table->string('position')->nullable();   // wo platziert (Slot/Seite)
            $table->string('ziel_url');
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
