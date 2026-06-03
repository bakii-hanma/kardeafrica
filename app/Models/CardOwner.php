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

    protected $fillable = [
        'business_name',
        'contact_name',
        'slug',
        'email',
        'phone',
        'whatsapp_number',
        'password',
        'city',
        'address',
        'business_type',
        'logo_url',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_active'      => 'boolean',
            'last_login_at'  => 'datetime',
        ];
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
