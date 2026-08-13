<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Code OTP de vérification d'un numéro (WhatsApp via WHAPI).
 * Le code n'est jamais stocké en clair (code_hash = bcrypt). Voir OtpService.
 */
class PhoneVerification extends Model
{
    public const PURPOSE_OWNER_REGISTER = 'owner_register';
    public const PURPOSE_OWNER_RESET    = 'owner_password_reset';
    /** Reset revendeur (guard vendor) — cloisonné des OTP propriétaire. */
    public const PURPOSE_VENDOR_RESET   = 'vendor_password_reset';

    /** Connexion client par WhatsApp — sert aussi de création de compte. */
    public const PURPOSE_CLIENT_LOGIN   = 'client_login';

    protected $fillable = [
        'phone', 'channel', 'purpose', 'code_hash',
        'attempts', 'expires_at', 'verified_at', 'last_sent_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'verified_at'  => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts'     => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
