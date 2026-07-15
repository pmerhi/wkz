<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Openverse liefert 36-stellige UUIDs als externe ID – die aus der Flickr-Zeit
 * stammende Spalte (VARCHAR 32) muss verbreitert werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ortbilder', function (Blueprint $table) {
            $table->string('extern_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ortbilder', function (Blueprint $table) {
            $table->string('extern_id', 32)->nullable()->change();
        });
    }
};
