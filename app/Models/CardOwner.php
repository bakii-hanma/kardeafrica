<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * CardOwner
 * ===
 * Propriétaire d'une (ou plusieurs) carte locale Carte Gabon. Distinct du
 * Reseller (qui vend les cartes API). Authentification email/mot de passe
 * via le guard `card_owner` — dashboard sous /proprietaire/*.
 */
class CardOwner extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    // ============================================================
    // Machine à états du compte (voir docs/PROJET_ETAT_ET_ROADMAP.md §3.2)
    // ============================================================
    public const STATUS_PENDING_OTP   = 'pending_otp';   // créé, numéro non vérifié
    public const STATUS_OTP_VERIFIED  = 'otp_verified';  // OTP OK, KYC à compléter
    public const STATUS_PROVISIONAL   = 'provisional';   // KYC soumis → accès création (brouillons)
    public const STATUS_ACTIVE        = 'active';        // validé admin → compte définitif
    public const STATUS_DOCS_REQUESTED = 'docs_requested'; // admin réclame des pièces
    public const STATUS_REJECTED      = 'rejected';      // refusé
    public const STATUS_SUSPENDED     = 'suspended';     // suspendu

    // NOTE M21 : status / is_active / reviewed_by NE sont PAS fillable — mutés
    // explicitement (affectation directe) aux seuls endroits légitimes.
    protected $fillable = [
        'business_name',
        'contact_name',
        'slug',
        'email',
        'phone',
        'whatsapp_number',
        'password',
        'city',
        'quartier',
        'address',
        'geo_lat',
        'geo_lng',
        'business_type',
        'logo_url',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'id_document_path',
        'business_document_path',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
            'kyc_submitted_at'  => 'datetime',
            'reviewed_at'       => 'datetime',
            'geo_lat'           => 'decimal:7',
            'geo_lng'           => 'decimal:7',
        ];
    }

    // ============================================================
    // Helpers d'état
    // ============================================================

    /** Le pro peut-il accéder à l'espace de création de cartes ? (provisoire ou définitif) */
    public function canCreateCards(): bool
    {
        return in_array($this->status, [
            self::STATUS_PROVISIONAL,
            self::STATUS_ACTIVE,
            self::STATUS_DOCS_REQUESTED, // garde l'accès pendant qu'il fournit les pièces
        ], true);
    }

    public function isProvisional(): bool { return $this->status === self::STATUS_PROVISIONAL; }
    public function isDefinitive(): bool  { return $this->status === self::STATUS_ACTIVE; }
    public function needsOtp(): bool      { return $this->status === self::STATUS_PENDING_OTP; }
    public function needsKyc(): bool      { return $this->status === self::STATUS_OTP_VERIFIED; }

    /** Libellé lisible de l'état, pour les bandeaux UI. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_OTP    => 'Numéro à vérifier',
            self::STATUS_OTP_VERIFIED   => 'Dossier à compléter',
            self::STATUS_PROVISIONAL    => 'Accès provisoire — en cours de validation',
            self::STATUS_ACTIVE         => 'Compte validé',
            self::STATUS_DOCS_REQUESTED => 'Pièces complémentaires demandées',
            self::STATUS_REJECTED       => 'Dossier refusé',
            self::STATUS_SUSPENDED      => 'Compte suspendu',
            default                     => 'Statut inconnu',
        };
    }

    // ============================================================
    // Slug auto
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $owner) {
            if (empty($owner->slug)) {
                $owner->slug = self::uniqueSlug($owner->business_name);
            }
        });
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'proprietaire';
        $candidate = $slug;
        $i = 2;
        while (self::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $slug.'-'.$i++;
        }
        return $candidate;
    }

    // ============================================================
    // Relations
    // ============================================================

    public function cards(): HasMany
    {
        return $this->hasMany(MerchantCard::class);
    }
}
