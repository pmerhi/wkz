<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kennzeichen_kuerzel_zulassungsstelle', function (Blueprint $table) {
            $table->unsignedBigInteger('kennzeichen_kuerzel_id');
            $table->unsignedBigInteger('zulassungsstelle_id');

            $table->foreign('kennzeichen_kuerzel_id', 'kkz_kuerzel_fk')
                  ->references('id')->on('kennzeichen_kuerzel')->cascadeOnDelete();
            $table->foreign('zulassungsstelle_id', 'kkz_stelle_fk')
                  ->references('id')->on('zulassungsstellen')->cascadeOnDelete();

            $table->primary(['kennzeichen_kuerzel_id', 'zulassungsstelle_id'], 'kkz_zst_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kennzeichen_kuerzel_zulassungsstelle');
    }
};
