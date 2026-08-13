<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantCardPurchase
 * ===
 * Achat d'un MerchantCard par un client. Contient :
 *  - le code 8 chiffres + le QR payload signé pour vérification
 *  - les coordonnées acheteur + destinataire (offrir à quelqu'un)
 *  - le solde restant (utilisations partielles autorisées)
 *  - le cycle de vie (inactive → active → partially_used → fully_used / expired)
 */
class MerchantCardPurchase extends Model
{
    use HasFactory;

    // Cycle de vie
    public const STATUS_INACTIVE        = 'inactive';        // créée mais pas encore payée
    public const STATUS_ACTIVE          = 'active';          // payée, utilisable
    public const STATUS_PARTIALLY_USED  = 'partially_used';  // débitée mais solde > 0
    public const STATUS_FULLY_USED      = 'fully_used';      // solde = 0
    public const STATUS_EXPIRED         = 'expired';
    public const STATUS_CANCELLED       = 'cancelled';

    public const PAYMENT_PENDING   = 'pending';
    public const PAYMENT_PAID      = 'paid';
    public const PAYMENT_FAILED    = 'failed';
    public const PAYMENT_REFUNDED  = 'refunded';

    protected $fillable = [
        'merchant_card_id',
        'order_id', 'order_item_id',
        'reseller_id', 'user_id', 'sold_by_reseller_at',
        'unique_code', 'pin_code', 'pin_hash', 'qr_payload',
        'buyer_name', 'buyer_phone', 'buyer_email',
        'recipient_name', 'recipient_phone', 'recipient_message',
        'amount', 'remaining_balance', 'currency',
        'admin_commission_amount', 'vendor_commission_amount', 'owner_net_amount',
        'payment_method', 'payment_status', 'payment_ref', 'ebilling_transaction_id',
        'status', 'delivery_channel', 'delivered_at',
        'expires_at', 'paid_at',
    ];

    protected $casts = [
        'amount'                    => 'decimal:2',
        'remaining_balance'         => 'decimal:2',
        'admin_commission_amount'   => 'decimal:2',
        'vendor_commission_amount'  => 'decimal:2',
        'owner_net_amount'          => 'decimal:2',
        'delivery_channel'          => 'array',
        'expires_at'                => 'datetime',
        'paid_at'                   => 'datetime',
        'delivered_at'              => 'datetime',
        'sold_by_reseller_at'       => 'datetime',
        // Le PIN en clair ne survit que le temps de la fenêtre de révélation :
        // chiffré au repos, effacé dès que le client l'a vu.
        'pin_code'                  => 'encrypted',
        'reveal_expires_at'         => 'datetime',
        'reveal_sent_at'            => 'datetime',
        'revealed_at'               => 'datetime',
    ];

    /**
     * Le QR payload n'est JAMAIS exposé en API publique (cf spec §SÉCURITÉ #1)
     * — il sert uniquement lors du scan, dans une route authentifiée vendor.
     */
    // SÉCURITÉ (H14) : le couple unique_code + pin_code EST le secret
    // d'authentification au comptoir — masquer le QR seul ne suffisait pas.
    // Masqué en JSON ; l'acheteur voit sa carte via le miroir UserCard, et le
    // scan marchand (Owner/ScanController) lit pin_code par accès propriété.
    protected $hidden = ['qr_payload', 'unique_code', 'pin_code', 'pin_hash', 'reveal_token_hash'];

    // ============================================================
    // Secret de la carte : PIN et révélation unique
    // ============================================================

    /** Durée de validité du lien envoyé au client. */
    public const REVEAL_TTL_MINUTES = 30;

    /** Nombre maximum d'envois du lien (le client peut ne pas recevoir le 1er). */
    public const REVEAL_MAX_SENDS = 3;

    /**
     * Vérifie le PIN saisi au comptoir du commerçant.
     * Le PIN n'est plus comparable en SQL : seul son condensat est conservé.
     */
    public function checkPin(?string $pin): bool
    {
        if ($pin === null || $pin === '' || empty($this->pin_hash)) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    /**
     * Code et PIN de la carte, pour le titulaire du compte auquel elle est
     * rattachée. Appelé exclusivement depuis l'espace client authentifié :
     * aucun écran revendeur ni administrateur ne passe par ici.
     *
     * @return array{code:string, pin:?string}
     */
    public function secretForOwner(): array
    {
        return ['code' => $this->unique_code, 'pin' => $this->pin_code];
    }

    /** Le secret est-il encore lisible ? (faux pour les cartes d'avant le dispositif) */
    public function secretIsReadable(): bool
    {
        return !empty($this->pin_code);
    }

    /** Le client a-t-il déjà vu son code ? */
    public function isRevealed(): bool
    {
        // Les cartes remises AVANT ce dispositif n'ont plus de PIN en clair (la
        // migration l'a effacé) : leur code est déjà chez le client, il n'est
        // plus révélable — et ne doit pas proposer un envoi qui échouerait.
        return $this->revealed_at !== null
            || ($this->sold_by_reseller_at !== null && empty($this->pin_code));
    }

    /** Un lien est-il actuellement ouvrable ? */
    public function revealLinkIsLive(): bool
    {
        return !$this->isRevealed()
            && $this->reveal_token_hash !== null
            && $this->reveal_expires_at !== null
            && $this->reveal_expires_at->isFuture();
    }

    /**
     * Émet un lien de révélation à usage unique et renvoie le jeton EN CLAIR —
     * la seule fois où il existe hors du message envoyé au client. Seul son
     * condensat est stocké : lire la base ne permet pas d'ouvrir le lien.
     */
    public function issueRevealToken(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'reveal_token_hash' => hash('sha256', $token),
            'reveal_expires_at' => now()->addMinutes(self::REVEAL_TTL_MINUTES),
        ])->save();

        return $token;
    }

    /** Le jeton présenté correspond-il au lien vivant ? */
    public function revealTokenMatches(string $token): bool
    {
        return $this->revealLinkIsLive()
            && hash_equals((string) $this->reveal_token_hash, hash('sha256', $token));
    }

    /**
     * Remet le code et le PIN au client, et consomme le lien.
     *
     * Le PIN n'est PAS effacé : la carte vit désormais dans le compte du client,
     * qui doit pouvoir la relire aussi longtemps qu'elle a du solde. C'est le
     * lien qui est à usage unique, pas le secret.
     *
     * COMPROMIS ASSUMÉ — effacer le PIN après affichage était plus sûr au repos :
     * plus personne, pas même une fuite de base, ne pouvait le relire. Le
     * conserver impose de le chiffrer (clé dans l'environnement, jamais en base)
     * plutôt que de le hacher. En échange, une capture d'écran ratée ne fait plus
     * perdre la carte — c'était la première cause de ticket support prévisible.
     *
     * `pin_hash` reste la seule chose que lit le commerçant : le chemin de scan
     * ne déchiffre jamais rien. Deux représentations, une par acteur.
     *
     * @return array{code:string, pin:string}|null  null si le lien est déjà consommé
     */
    public function revealOnce(string $channel, ?string $ip = null): ?array
    {
        return DB::transaction(function () use ($channel, $ip) {
            /** @var self $verrou */
            $verrou = self::whereKey($this->getKey())->lockForUpdate()->first();

            // Deux ouvertures simultanées du même lien : la seconde ne voit rien.
            if ($verrou->revealed_at !== null || empty($verrou->pin_code)) {
                return null;
            }

            $secret = ['code' => $verrou->unique_code, 'pin' => $verrou->pin_code];

            $verrou->forceFill([
                'reveal_token_hash' => null,
                'reveal_expires_at' => null,
                'revealed_at'       => now(),
                'revealed_ip'       => $ip,
                'reveal_channel'    => $channel,
            ])->save();

            Log::info('CarteGabon: code révélé au client', [
                'purchase_id' => $verrou->id,
                'channel'     => $channel,
                'reseller_id' => $verrou->reseller_id,
            ]);

            $this->setRawAttributes($verrou->getAttributes(), true);

            return $secret;
        });
    }

    // ============================================================
    // Relations
    // ============================================================

    public function merchantCard(): BelongsTo
    {
        return $this->belongsTo(MerchantCard::class);
    }

    /** Compte client auquel la carte est rattachée, s'il existe. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(MerchantCardRedemption::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** Revendeur ayant vendu la carte au comptoir (NULL = achat en ligne). */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    // ============================================================
    // Helpers
    // ============================================================

    /** Est-elle encore utilisable maintenant ? */
    public function isRedeemable(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_USED], true)
            && $this->expires_at?->isFuture()
            && (float) $this->remaining_balance > 0;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
