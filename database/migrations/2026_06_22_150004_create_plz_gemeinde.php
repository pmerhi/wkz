<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plz_gemeinde', function (Blueprint $table) {
            $table->id();
            $table->string('plz', 5)->index();
            $table->foreignId('gemeinde_id')->constrained('gemeinden')->cascadeOnDelete();
            $table->foreignId('kreis_id')->nullable()->constrained('kreise')->nullOnDelete();
            $table->unique(['plz', 'gemeinde_id'], 'plz_gemeinde_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plz_gemeinde');
    }
};
