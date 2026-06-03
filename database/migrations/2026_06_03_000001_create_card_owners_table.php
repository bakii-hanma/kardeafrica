<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propriétaires de cartes locales (Carte Gabon).
 *
 * Population distincte des Resellers : un CardOwner est un commerçant à qui
 * appartient une (ou plusieurs) carte locale créée par l'admin. Il dispose
 * d'un dashboard dédié (/proprietaire/*) et scanne/valide les achats au
 * comptoir. Pas de wallet ni de KYC pour démarrer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_owners', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 120);
            $table->string('contact_name', 120);
            $table->string('slug', 140)->unique();
            $table->string('email', 190)->unique();
            $table->string('phone', 30);
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('password');
            $table->string('city', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('business_type', 60)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_owners');
    }
};
