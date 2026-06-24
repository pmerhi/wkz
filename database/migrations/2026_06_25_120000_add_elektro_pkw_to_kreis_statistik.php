<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            $table->unsignedInteger('elektro_pkw')->nullable()->after('pkw_bestand');
        });
    }

    public function down(): void
    {
        Schema::table('kreis_statistik', function (Blueprint $table) {
            $table->dropColumn('elektro_pkw');
        });
    }
};
