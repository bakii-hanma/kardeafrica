<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed les taux de change FCFA configurables depuis l'admin.
     *
     * Justification des défauts (vs taux marché XAF) :
     *  - EUR : peg fixe = 655.957. On stocke 750 → ~14% de marge.
     *  - USD : marché ≈ 590-620. On stocke 700 → ~13% de marge.
     *  - AED : marché ≈ 170. On stocke 200 → ~18% de marge.
     *
     * `currency_round_step` = 100 → tout prix FCFA est arrondi au prochain
     * multiple de 100 (1016 → 1100, 6560 → 6600). Évite les prix non-ronds
     * qui paraissent peu pro et facilite les paiements Mobile Money.
     *
     * Idempotent : pas d'écrasement si une valeur existe déjà (préserve les
     * réglages admin si la migration est rejouée par erreur).
     */
    public function up(): void
    {
        $defaults = [
            'currency_rate_eur'   => '750',
            'currency_rate_usd'   => '700',
            'currency_rate_aed'   => '200',
            'currency_round_step' => '100',
        ];

        foreach ($defaults as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        AppSetting::whereIn('key', [
            'currency_rate_eur',
            'currency_rate_usd',
            'currency_rate_aed',
            'currency_round_step',
        ])->delete();
    }
};
