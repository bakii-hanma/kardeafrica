<?php

use App\Models\MerchantCard;
use App\Support\MerchantCardCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Code 8 chiffres + PIN 4 chiffres + date d'expiration directement sur la
 * carte template (= comme une vraie carte-cadeau physique avec son propre
 * code imprimé). À la création par l'admin, ces 3 champs sont auto-générés.
 *
 * Les achats clients génèrent encore leur propre code+PIN (par purchase)
 * pour la sécurité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            // 8 chiffres unique, affiché à l'admin et utilisable comme master code
            $table->string('unique_code', 8)->nullable()->unique()->after('visual_url');
            // 4 chiffres pour valider l'accès au code
            $table->string('pin_code', 4)->nullable()->after('unique_code');
            // Date d'expiration calculée (= now + validity_months à la création)
            $table->date('expires_at')->nullable()->after('pin_code');
        });

        // Backfill : génère un code+PIN+expiration pour chaque carte existante
        MerchantCard::query()->whereNull('unique_code')->each(function (MerchantCard $c) {
            $code = self::generateUniqueMasterCode();
            $pin  = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exp  = now()->addMonths((int) ($c->validity_months ?? 12))->toDateString();
            $c->update([
                'unique_code' => $code,
                'pin_code'    => $pin,
                'expires_at'  => $exp,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->dropUnique(['unique_code']);
            $table->dropColumn(['unique_code', 'pin_code', 'expires_at']);
        });
    }

    /** Génère un code 8 chiffres unique sur merchant_cards (≠ merchant_card_purchases) */
    private static function generateUniqueMasterCode(int $maxAttempts = 8): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = (string) random_int(10000000, 99999999);
            if (!MerchantCard::where('unique_code', $code)->exists()) {
                return $code;
            }
        }
        throw new \RuntimeException("Impossible de générer un code unique pour la carte template.");
    }
};
