<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('metable');          // metable_type + metable_id (+ index)
            $table->string('title')->nullable();
            $table->string('description', 320)->nullable();
            $table->string('canonical')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->unique(['metable_type', 'metable_id'], 'seo_meta_metable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
    }
};
