<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding pro/commerçant : ajoute la machine à états + le KYC sur card_owners.
 *
 * Voir docs/PROJET_ETAT_ET_ROADMAP.md §3. Le compte pro EST un CardOwner ; son
 * cycle de vie (pending_otp → otp_verified → provisional → active …) porte la
 * validation provisoire (auto) puis définitive (admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_owners', function (Blueprint $table) {
            // Machine à états du compte (voir CardOwner::STATUS_*)
            $table->string('status', 20)->default('pending_otp')->after('is_active')->index();

            // KYC / dossier commerçant
            $table->string('quartier', 120)->nullable()->after('city');
            $table->decimal('geo_lat', 10, 7)->nullable()->after('quartier');
            $table->decimal('geo_lng', 10, 7)->nullable()->after('geo_lat');

            // Pièces justificatives — chemins sur disque PRIVÉ (jamais doc-root public)
            $table->string('id_document_path', 255)->nullable()->after('logo_url');
            $table->string('business_document_path', 255)->nullable()->after('id_document_path');

            // Revue admin
            $table->timestamp('kyc_submitted_at')->nullable()->after('business_document_path');
            $table->timestamp('reviewed_at')->nullable()->after('kyc_submitted_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            $table->text('review_notes')->nullable()->after('reviewed_by');
            $table->text('docs_requested_note')->nullable()->after('review_notes');
        });

        // Les comptes existants ont été créés par l'admin (is_active=true) → actifs.
        DB::table('card_owners')->where('is_active', true)->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('card_owners', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'quartier', 'geo_lat', 'geo_lng',
                'id_document_path', 'business_document_path',
                'kyc_submitted_at', 'reviewed_at', 'reviewed_by',
                'review_notes', 'docs_requested_note',
            ]);
        });
    }
};
