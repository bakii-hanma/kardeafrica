<?php

use App\Models\MerchantCardPurchase;
use App\Support\MerchantCardCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Backfill one-shot pour les MerchantCardPurchase créées AVANT l'ajout
 * du PIN + UserCard miroir. Pour chaque purchase qui a un order_item_id :
 *  - génère un pin_code s'il manque
 *  - crée la UserCard miroir si absente
 *
 * Idempotent grâce à backfillPinAndUserCard(). Si on relance les migrations
 * (fresh par exemple), pas de doublon.
 */
return new class extends Migration
{
    public function up(): void
    {
        $purchases = MerchantCardPurchase::whereNotNull('order_item_id')
            ->whereNotNull('order_id')
            ->with(['order', 'orderItem'])
            ->get();

        $fixed = 0;
        foreach ($purchases as $purchase) {
            if (!$purchase->order || !$purchase->orderItem) continue;

            try {
                MerchantCardCode::backfillPinAndUserCard(
                    $purchase,
                    $purchase->orderItem,
                    $purchase->order
                );
                $fixed++;
            } catch (\Throwable $e) {
                Log::warning('Backfill: échec purchase #'.$purchase->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Backfill migration: {$fixed} purchases traitées sur " . $purchases->count());
    }

    public function down(): void
    {
        // Pas de rollback automatique : on ne sait pas distinguer les UserCard
        // créées par ce backfill des autres. Manuel si besoin.
    }
};
