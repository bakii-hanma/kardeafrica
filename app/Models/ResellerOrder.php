<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerOrder extends Model
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_FAILED     = 'failed';
    // État transitoire : virement de remboursement en cours auprès du PSP.
    // Marqué DANS un verrou avant l'appel transfer.php pour empêcher tout
    // double remboursement (voir Vendor\SaleController::refund).
    const STATUS_REFUNDING  = 'refunding';
    const STATUS_REFUNDED   = 'refunded';

    const PAYMENT_PENDING   = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED    = 'failed';
    const PAYMENT_REFUNDED  = 'refunded';

    protected $fillable = [
        'reseller_id',
        'order_number',
        'claim_token', 'user_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'commission_earned',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'external_reference',
        'claimed_at',
        'expires_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'subtotal'          => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'claimed_at'        => 'datetime',
        'claim_expires_at'  => 'datetime',
        'claim_sent_at'     => 'datetime',
        'expires_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $o) {
            if (empty($o->order_number)) {
                $o->order_number = 'KV' . now()->format('YmdHis') . strtoupper(Str::random(3));
            }
            // Plus de jeton posé à la création : un lien qui existe avant d'être
            // demandé est un lien qui traîne. Il est émis à l'envoi au client,
            // à usage unique et expirant (cf. `issueClaimToken`).
            if (empty($o->expires_at)) {
                $o->expires_at = now()->addDays(30);
            }
        });
    }

    // ------------------------------------------------------------------
    // Remise des cartes au client
    // ------------------------------------------------------------------

    /** Durée de validité du lien envoyé au client. */
    public const CLAIM_TTL_MINUTES = 60;

    /** Nombre maximum d'envois (le client peut ne pas recevoir le premier). */
    public const CLAIM_MAX_SENDS = 3;

    /**
     * Émet un lien de récupération et renvoie le jeton EN CLAIR — la seule fois
     * où il existe hors du message envoyé au client. Seul son condensat est
     * stocké : lire la base ne permet pas d'ouvrir le lien.
     */
    public function issueClaimToken(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'claim_token'      => null,
            'claim_token_hash' => hash('sha256', $token),
            'claim_expires_at' => now()->addMinutes(self::CLAIM_TTL_MINUTES),
        ])->save();

        return $token;
    }

    /** Un lien est-il actuellement ouvrable ? */
    public function claimLinkIsLive(): bool
    {
        return $this->claimed_at === null
            && $this->claim_token_hash !== null
            && $this->claim_expires_at !== null
            && $this->claim_expires_at->isFuture();
    }

    public function claimTokenMatches(string $token): bool
    {
        return $this->claimLinkIsLive()
            && hash_equals((string) $this->claim_token_hash, hash('sha256', $token));
    }

    /**
     * Consomme le lien. Les cartes, elles, restent dans le compte du client :
     * c'est le LIEN qui est à usage unique, pas les codes.
     */
    public function consumeClaimLink(string $channel, ?string $ip = null): bool
    {
        return (bool) DB::transaction(function () use ($channel, $ip) {
            $verrou = self::whereKey($this->getKey())->lockForUpdate()->first();

            if ($verrou->claimed_at !== null) {
                return false;
            }

            $verrou->forceFill([
                'claim_token_hash' => null,
                'claim_expires_at' => null,
                'claimed_at'       => now(),
                'claimed_ip'       => $ip,
                'claim_channel'    => $channel,
            ])->save();

            $this->setRawAttributes($verrou->getAttributes(), true);

            return true;
        });
    }

    /** Compte client auquel les cartes sont rattachées. */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerOrderItem::class);
    }

    public function cards()
    {
        return $this->hasMany(ResellerCard::class);
    }

    public function getIsClaimableAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->expires_at?->isFuture();
    }
}
