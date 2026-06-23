<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratgeber_artikel_tag', function (Blueprint $table) {
            $table->foreignId('ratgeber_artikel_id')
                  ->constrained('ratgeber_artikel')->cascadeOnDelete();
            $table->foreignId('tag_id')
                  ->constrained('tags')->cascadeOnDelete();
            $table->primary(['ratgeber_artikel_id', 'tag_id'], 'ratgeber_tag_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratgeber_artikel_tag');
    }
};
