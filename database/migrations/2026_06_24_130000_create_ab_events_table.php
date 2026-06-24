<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_events', function (Blueprint $table) {
            $table->id();
            $table->string('experiment', 64);
            $table->string('variant', 16);
            $table->string('event', 16);            // exposure | conversion
            $table->string('label')->nullable();    // z. B. zst:wuerzburg, ort:muenchen
            $table->string('campaign', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['experiment', 'variant', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_events');
    }
};
