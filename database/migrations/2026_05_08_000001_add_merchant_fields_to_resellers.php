<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module « Carte Gabon »
 * ===
 * Extend resellers table to support :
 *  - publicly listed merchants (slug, business_type, description, location)
 *  - KYC workflow (status, documents JSON)
 *  - visual identity (logo + cover image)
 *  - geolocation (for "stores near me" later)
 *  - mobile money payout account (where the merchant receives their share)
 *
 * Backward-compatible : tous les nouveaux champs sont nullable, le vendor existant
 * continue de fonctionner identiquement (kyc_status=null = pas un merchant Carte
 * Gabon, juste un revendeur du catalogue afrikard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            // ===== Identité publique du marchand =====
            // slug unique pour /gabon/marchand/{slug} — généré au moment de l'activation
            $table->string('slug', 80)->nullable()->after('vendor_code');
            // Le "nom commercial" (peut différer du nom du propriétaire)
            $table->string('business_name', 150)->nullable()->after('name');
            // Catégorie pour la vitrine + filtres
            $table->string('business_type', 40)->nullable()->after('business_name');
            $table->text('description')->nullable()->after('business_type');

            // ===== Géolocalisation =====
            $table->string('address', 255)->nullable()->after('description');
            $table->string('city', 80)->nullable()->default('Libreville')->after('address');
            $table->string('province', 80)->nullable()->default('Estuaire')->after('city');
            $table->decimal('geo_lat', 10, 7)->nullable()->after('province');
            $table->decimal('geo_lng', 10, 7)->nullable()->after('geo_lat');

            // ===== Visuels =====
            $table->string('logo_url', 500)->nullable()->after('geo_lng');
            $table->string('cover_url', 500)->nullable()->after('logo_url');

            // ===== KYC =====
            // pending = inscription en attente, approved = peut vendre, rejected, suspended
            $table->string('kyc_status', 20)->nullable()->default('pending')->after('cover_url');
            $table->json('kyc_documents')->nullable()->after('kyc_status'); // ['id_card' => url, 'rcc' => url, …]
            $table->timestamp('kyc_approved_at')->nullable()->after('kyc_documents');
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_approved_at');

            // ===== Mobile money payout =====
            // Compte vers lequel KardAfrica reverse la part du marchand (commission déduite)
            $table->string('mobile_money_provider', 20)->nullable()->after('kyc_rejection_reason'); // 'airtel' | 'moov'
            $table->string('mobile_money_account', 30)->nullable()->after('mobile_money_provider');
            $table->string('whatsapp_number', 30)->nullable()->after('mobile_money_account');

            // ===== Index =====
            $table->index('slug');
            $table->index(['business_type', 'kyc_status', 'is_active'], 'resellers_marketplace_idx');
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropIndex('resellers_marketplace_idx');
            $table->dropIndex(['slug']);
            $table->dropColumn([
                'slug', 'business_name', 'business_type', 'description',
                'address', 'city', 'province', 'geo_lat', 'geo_lng',
                'logo_url', 'cover_url',
                'kyc_status', 'kyc_documents', 'kyc_approved_at', 'kyc_rejection_reason',
                'mobile_money_provider', 'mobile_money_account', 'whatsapp_number',
            ]);
        });
    }
};
