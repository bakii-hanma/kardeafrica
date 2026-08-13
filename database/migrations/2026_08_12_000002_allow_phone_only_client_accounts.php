<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes clients créés sur un simple numéro WhatsApp.
 *
 * Le client au comptoir n'a pas d'adresse e-mail à donner, et n'en aura jamais
 * besoin : l'application ne lui envoie aucun courriel — elle est WhatsApp-first
 * (aucun `Mail::to()` ni `->notify()` dans le code applicatif). `email` devient
 * donc facultatif. L'index unique le reste : MySQL tolère plusieurs NULL.
 *
 * `password` n'est PAS rendu nullable : un compte auto-créé reçoit un mot de
 * passe aléatoire que personne ne connaît. C'est plus sûr qu'une colonne nulle,
 * qui obligerait chaque chemin d'authentification à se défendre contre elle.
 *
 * Les numéros existants sont ramenés à la forme canonique : le numéro devient
 * la clé du compte, deux écritures de la même ligne créeraient deux comptes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();

            // D'où vient ce compte — répond à une vraie question du support :
            // « ce compte a-t-il été créé par le client, ou au comptoir ? »
            $table->string('created_via', 30)->nullable()->after('role');
        });

        // Le numéro devient une clé de recherche : sans index, chaque vente au
        // comptoir déclencherait un balayage complet de la table.
        Schema::table('users', function (Blueprint $table) {
            $table->index('phone', 'users_phone_index');
        });

        // Reprise : forme canonique pour les numéros déjà présents.
        DB::table('users')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderBy('id')
            ->select('id', 'phone')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $canonique = Phone::normalize($row->phone);

                    if ($canonique !== null && $canonique !== $row->phone) {
                        DB::table('users')->where('id', $row->id)->update(['phone' => $canonique]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_phone_index');
            $table->dropColumn('created_via');
            // `email` ne redevient pas obligatoire : des comptes sans adresse
            // existent désormais, la contrainte échouerait.
        });
    }
};
