<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            // Öffentliche Ladepunkte je Kreis (Bundesnetzagentur-Ladesäulenregister).
            $table->integer('ladepunkte_normal')->nullable()->after('elektro_pkw');
            $table->integer('ladepunkte_schnell')->nullable()->after('ladepunkte_normal');
            $table->string('ladepunkte_stand', 20)->nullable()->after('ladepunkte_schnell');
        });
    }

    public function down(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            $table->dropColumn(['ladepunkte_normal', 'ladepunkte_schnell', 'ladepunkte_stand']);
        });
    }
};
