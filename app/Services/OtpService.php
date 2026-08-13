<?php

namespace App\Services;

use App\Models\PhoneVerification;
use Illuminate\Support\Facades\Hash;

/**
 * OtpService — génération, envoi et vérification des codes OTP WhatsApp.
 *
 * Sécurité :
 *   - Code à 6 chiffres généré par random_int (cryptographique).
 *   - Stocké HACHÉ (bcrypt), jamais en clair.
 *   - TTL court (10 min) + plafond de tentatives (5) → anti brute-force.
 *   - Anti-spam d'envoi : 60 s minimum entre deux envois pour un même numéro.
 */
class OtpService
{
    public const TTL_MINUTES     = 10;
    public const MAX_ATTEMPTS    = 5;
    public const RESEND_COOLDOWN = 60; // secondes

    public function __construct(private WhapiService $whapi) {}

    /**
     * Génère un code, le persiste (haché) et l'envoie par WhatsApp.
     *
     * @return array{sent:bool, cooldown:bool, seconds:int}
     */
    public function sendOtp(string $phoneE164, string $purpose = PhoneVerification::PURPOSE_OWNER_REGISTER): array
    {
        $existing = PhoneVerification::where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        // Anti-spam : respecter le cooldown entre deux envois.
        if ($existing && $existing->last_sent_at
            && $existing->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN) {
            $wait = self::RESEND_COOLDOWN - $existing->last_sent_at->diffInSeconds(now());
            return ['sent' => false, 'cooldown' => true, 'seconds' => max(1, $wait)];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerification::updateOrCreate(
            ['phone' => $phoneE164, 'purpose' => $purpose],
            [
                'channel'      => 'whatsapp',
                'code_hash'    => Hash::make($code),
                'attempts'     => 0,
                'verified_at'  => null,
                'expires_at'   => now()->addMinutes(self::TTL_MINUTES),
                'last_sent_at' => now(),
            ]
        );

        $message = "KardAfrica — votre code de vérification est : {$code}\n"
            . "Il expire dans " . self::TTL_MINUTES . " minutes. Ne le partagez avec personne.";

        $sent = $this->whapi->sendText($phoneE164, $message);

        return ['sent' => $sent, 'cooldown' => false, 'seconds' => 0];
    }

    /**
     * Vérifie un code saisi. Consomme une tentative en cas d'échec.
     *
     * @return array{ok:bool, reason:?string}
     */
    public function verify(string $phoneE164, string $code, string $purpose = PhoneVerification::PURPOSE_OWNER_REGISTER): array
    {
        $record = PhoneVerification::where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (!$record) {
            return ['ok' => false, 'reason' => 'Aucun code en cours. Demande un nouveau code.'];
        }
        if ($record->verified_at) {
            return ['ok' => true, 'reason' => null]; // déjà vérifié → idempotent
        }
        if ($record->isExpired()) {
            return ['ok' => false, 'reason' => 'Code expiré. Demande un nouveau code.'];
        }
        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'reason' => 'Trop de tentatives. Demande un nouveau code.'];
        }

        if (!Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');
            return ['ok' => false, 'reason' => 'Code incorrect.'];
        }

        $record->update(['verified_at' => now()]);
        return ['ok' => true, 'reason' => null];
    }

    /** Le numéro a-t-il un OTP vérifié récent pour ce purpose ? */
    public function isVerified(string $phoneE164, string $purpose = PhoneVerification::PURPOSE_OWNER_REGISTER): bool
    {
        return PhoneVerification::where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->exists();
    }

    /**
     * Normalise un numéro gabonais vers le format E.164 sans '+'.
     * Ex : "06 87 13 09" / "+241 06 87 13 09" / "0687130..." → "24106871309".
     */
    public static function normalizeGabon(string $raw): string
    {
        // Délègue à `App\Support\Phone`, seule normalisation du projet. Cette
        // version gabonisait tout, y compris un numéro étranger explicite —
        // l'OTP partait alors sur une ligne qui n'existe pas.
        return \App\Support\Phone::normalize($raw) ?? '';
    }
}
