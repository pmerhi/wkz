<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_scores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedSmallInteger('richtige')->default(0);   // korrekt beantwortete Fragen
            $table->timestamp('created_at')->nullable();

            $table->index(['score']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_scores');
    }
};
