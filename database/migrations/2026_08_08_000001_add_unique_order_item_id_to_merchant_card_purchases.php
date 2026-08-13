<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * H16 — Cartes marchand dupliquées
 * ===
 * createPurchaseForOrderItem() faisait son contrôle d'existence HORS transaction :
 * deux requêtes simultanées (deux onglets sur la page commande → self-heal)
 * pouvaient créer DEUX MerchantCardPurchase (+ deux UserCard miroir) pour un
 * seul OrderItem payé.
 *
 * 1. Dédoublonnage : par order_item_id non-null, on garde UNE purchase
 *    (celle au plus petit id, sauf si une autre a déjà des redemptions —
 *    on ne détruit jamais un historique d'encaissement) et on supprime les
 *    autres AINSI QUE leurs UserCard miroir (liées via
 *    metadata.merchant_purchase_id). Les redemptions des doublons supprimés
 *    partent par cascadeOnDelete (cas normalement vide, cf. choix du keeper).
 * 2. Contrainte UNIQUE sur merchant_card_purchases.order_item_id — filet de
 *    sécurité définitif côté BDD. Colonne nullable : MySQL/SQLite autorisent
 *    plusieurs NULL sur un index unique, donc les purchases sans Order
 *    (achat direct) ne sont pas affectées.
 *
 * Uniquement DB::table ici (règle M14) : pas de modèles Eloquent dans les
 * migrations (les modèles évoluent, la migration doit rester rejouable).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Dédoublonnage des purchases partageant le même order_item_id
        $dupGroups = DB::table('merchant_card_purchases')
            ->select('order_item_id')
            ->whereNotNull('order_item_id')
            ->groupBy('order_item_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_item_id');

        foreach ($dupGroups as $orderItemId) {
            $rows = DB::table('merchant_card_purchases')
                ->where('order_item_id', $orderItemId)
                ->orderBy('id')
                ->get(['id']);

            // Choix du keeper : plus petit id, SAUF si un doublon a déjà été
            // encaissé (redemptions) — dans ce cas on garde celui-là pour ne
            // pas perdre l'audit trail financier.
            $ids = $rows->pluck('id')->all();
            $redeemedIds = DB::table('merchant_card_redemptions')
                ->whereIn('merchant_card_purchase_id', $ids)
                ->distinct()
                ->pluck('merchant_card_purchase_id')
                ->all();

            $keepId = !empty($redeemedIds) ? min($redeemedIds) : min($ids);

            if (count($redeemedIds) > 1) {
                // Deux doublons encaissés pour un seul item payé : perte réelle.
                // On dédoublonne quand même (la contrainte UNIQUE doit passer)
                // mais on trace pour réconciliation manuelle.
                Log::critical('H16 dedup: plusieurs purchases ENCAISSÉES pour le même order_item — réconciliation manuelle requise', [
                    'order_item_id' => $orderItemId,
                    'redeemed_ids'  => $redeemedIds,
                    'kept_id'       => $keepId,
                ]);
            }

            $dupIds = array_values(array_diff($ids, [$keepId]));

            // Supprime les UserCard miroir des doublons (liées via
            // metadata.merchant_purchase_id — décodage en PHP pour rester
            // portable MySQL/SQLite, pas de whereJson ici).
            $mirrorIds = DB::table('user_cards')
                ->where('order_item_id', $orderItemId)
                ->get(['id', 'metadata'])
                ->filter(function ($row) use ($dupIds) {
                    $meta = json_decode($row->metadata ?? '', true);
                    return is_array($meta)
                        && in_array((int) ($meta['merchant_purchase_id'] ?? 0), $dupIds, true);
                })
                ->pluck('id')
                ->all();

            if (!empty($mirrorIds)) {
                DB::table('user_cards')->whereIn('id', $mirrorIds)->delete();
            }

            DB::table('merchant_card_purchases')->whereIn('id', $dupIds)->delete();

            Log::warning('H16 dedup: doublons de MerchantCardPurchase supprimés', [
                'order_item_id'    => $orderItemId,
                'kept_id'          => $keepId,
                'deleted_ids'      => $dupIds,
                'deleted_mirrors'  => $mirrorIds,
            ]);
        }

        // 2. Filet de sécurité définitif : une seule purchase par order_item.
        //    (nullable → les NULL multiples restent autorisés)
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->unique('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropUnique(['order_item_id']);
        });
        // Les lignes dédoublonnées ne sont pas restaurées (comme tout dedup).
    }
};
