<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes de vérification OTP (WhatsApp via WHAPI).
 *
 * Le code n'est JAMAIS stocké en clair : seul son hash bcrypt est conservé.
 * TTL court + compteur de tentatives (anti brute-force). Voir OtpService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30);                    // format E.164 (2410...)
            $table->string('channel', 20)->default('whatsapp');
            $table->string('purpose', 40)->default('owner_register');
            $table->string('code_hash');                    // bcrypt du code — jamais en clair
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
