<?php

use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Models\UserCard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wipe one-shot : supprime TOUTES les cartes locales existantes pour repartir
 * de zéro avec le nouveau modèle (admin = créateur unique, pas d'approbation).
 *
 * Ordre de suppression :
 *  1. UserCard mirrors (metadata.source = 'merchant') — pas de FK auto, manuel
 *  2. MerchantCardPurchase — cascade via merchant_card_id mais on log avant
 *  3. MerchantCardRedemption — cascade via purchase
 *  4. MerchantCard — final
 *
 * Le admin (= toi) recréera ensuite des cartes fraîches via /admin/merchant-cards/nouvelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. UserCard miroirs des cartes marchand (pas de FK = suppression manuelle)
        $userCardsDeleted = UserCard::query()
            ->where('metadata->source', 'merchant')
            ->delete();

        // 2. MerchantCardPurchase (pour le log — sera cascade via merchant_card delete,
        //    mais on les vire explicitement pour aussi nettoyer celles qui n'ont
        //    plus de merchant_card valide)
        $purchasesDeleted = MerchantCardPurchase::query()->delete();

        // 3. MerchantCard (cascade naturelle sur redemptions)
        $cardsDeleted = MerchantCard::query()->delete();

        Log::info('Wipe migration: cartes locales supprimées', [
            'user_cards_deleted' => $userCardsDeleted,
            'purchases_deleted'  => $purchasesDeleted,
            'cards_deleted'      => $cardsDeleted,
        ]);
    }

    public function down(): void
    {
        // Pas de rollback : la suppression est irréversible.
    }
};
