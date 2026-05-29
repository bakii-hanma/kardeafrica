<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nettoyage complet « Carte Gabon » : les cartes locales forment un catalogue
 * admin global et n'appartiennent plus jamais à un marchand. On supprime donc
 * définitivement la colonne reseller_id (+ FK + index) sur les trois tables.
 *
 * Les noms de FK/index sont résolus dynamiquement pour rester robuste quelle
 * que soit l'historique des migrations (renommages MySQL/MariaDB).
 */
return new class extends Migration
{
    private array $tables = [
        'merchant_cards',
        'merchant_card_purchases',
        'merchant_card_redemptions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasColumn($tableName, 'reseller_id')) {
                continue;
            }

            // 1. Supprime toute clé étrangère portant sur reseller_id
            foreach (Schema::getForeignKeys($tableName) as $fk) {
                if (in_array('reseller_id', $fk['columns'], true)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($fk['name']));
                }
            }

            // 2. Supprime tout index (non-primaire) impliquant reseller_id
            foreach (Schema::getIndexes($tableName) as $index) {
                if (!($index['primary'] ?? false) && in_array('reseller_id', $index['columns'], true)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($index['name']));
                }
            }

            // 3. Supprime la colonne
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('reseller_id'));
        }
    }

    public function down(): void
    {
        // Réintroduit reseller_id en nullable (état antérieur), avec FK nullOnDelete.
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'reseller_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('reseller_id')->nullable();
                $table->foreign('reseller_id')->references('id')->on('resellers')->nullOnDelete();
            });
        }
    }
};
