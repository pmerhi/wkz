<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->timestamp('clicked_at')->useCurrent();
            $table->string('referrer')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->index(['placement_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
