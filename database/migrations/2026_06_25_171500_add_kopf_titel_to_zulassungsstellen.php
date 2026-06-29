<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            // Fixe Kopfzeilen-Bezeichnung des Amts, die auf der Detailseite
            // anstelle des Portal-Logos angezeigt wird, z.B.
            // „Straßenverkehrsamt München" bzw. „Zulassungsstelle Aichach".
            $table->string('kopf_titel')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('zulassungsstellen', function (Blueprint $table) {
            $table->dropColumn('kopf_titel');
        });
    }
};
