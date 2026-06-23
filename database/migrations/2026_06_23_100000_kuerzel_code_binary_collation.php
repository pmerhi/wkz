<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kfz-Unterscheidungszeichen unterscheiden sich durch Umlaute (BO ≠ BÖ,
 * GO ≠ GÖ …). Die bisherige akzent-/case-insensitive Kollation
 * (utf8mb4_unicode_ci) behandelte sie als gleich → der Unique-Index ließ
 * Umlaut-Codes nicht eigenständig zu und Lookups trafen das falsche Pendant.
 * utf8mb4_bin macht `code` exakt (byte-genau) eindeutig.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nur MySQL/MariaDB: SQLite (Tests) ist ohnehin byte-genau und kennt MODIFY/COLLATE nicht.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('ALTER TABLE kennzeichen_kuerzel MODIFY code VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('ALTER TABLE kennzeichen_kuerzel MODIFY code VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
