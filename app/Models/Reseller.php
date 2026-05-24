<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Reseller extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'vendor_code',
        'slug',                  // Phase 1 Carte Gabon — vitrine publique
        'name',
        'business_name', 'business_type', 'description',
        'phone',
        'email',
        'password',
        'wallet_balance',
        'commission_balance',
        'wallet_locked',
        'cash_to_remit',
        'max_wallet',
        'commission_rate',
        'total_commission_earned',
        'total_volume',
        'is_active',
        'last_login_at',
        // Carte Gabon : géolocalisation
        'address', 'city', 'province', 'geo_lat', 'geo_lng',
        // Carte Gabon : visuels
        'logo_url', 'cover_url',
        // Carte Gabon : KYC
        'kyc_status', 'kyc_documents', 'kyc_approved_at', 'kyc_rejection_reason',
        // Carte Gabon : payout
        'mobile_money_provider', 'mobile_money_account', 'whatsapp_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'                  => 'hashed',
            'wallet_balance'            => 'decimal:2',
            'commission_balance'        => 'decimal:2',
            'wallet_locked'             => 'decimal:2',
            'cash_to_remit'             => 'decimal:2',
            'max_wallet'                => 'decimal:2',
            'commission_rate'           => 'decimal:2',
            'total_commission_earned'   => 'decimal:2',
            'total_volume'              => 'decimal:2',
            'is_active'                 => 'boolean',
            'last_login_at'             => 'datetime',
            // Carte Gabon
            'geo_lat'                   => 'decimal:7',
            'geo_lng'                   => 'decimal:7',
            'kyc_documents'             => 'array',
            'kyc_approved_at'           => 'datetime',
        ];
    }

    // ---- Relations ----
    public function walletTransactions()
    {
        return $this->hasMany(ResellerWalletTransaction::class)->latest();
    }

    public function orders()
    {
        return $this->hasMany(ResellerOrder::class);
    }

    public function cashRemittances()
    {
        return $this->hasMany(ResellerCashRemittance::class)->latest();
    }

    // ---- Carte Gabon (Phase 1) ----

    public function merchantCards()
    {
        return $this->hasMany(MerchantCard::class);
    }

    public function merchantCardPurchases()
    {
        return $this->hasMany(MerchantCardPurchase::class);
    }

    public function merchantRedemptions()
    {
        return $this->hasMany(MerchantCardRedemption::class);
    }

    public function merchantUsers()
    {
        return $this->hasMany(MerchantUser::class);
    }

    /** True si ce vendor a une activité "Carte Gabon" (= KYC validé) */
    public function isApprovedMerchant(): bool
    {
        return $this->kyc_status === 'approved' && $this->is_active;
    }

    // ---- Helpers ----

    /**
     * Génère un code vendeur unique court & lisible (ex: KA-V-7H3K)
     */
    public static function generateVendorCode(): string
    {
        do {
            $code = 'KA-V-' . strtoupper(Str::random(4));
        } while (static::where('vendor_code', $code)->exists());
        return $code;
    }

    /**
     * Crédite le portefeuille (action admin).
     * Renvoie le solde après crédit, throws si dépasse le plafond.
     */
    public function credit(float $amount, ?int $adminId = null, ?string $description = null, ?string $reference = null): float
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($amount, $adminId, $description, $reference) {
            $this->refresh();
            $newBalance = (float) $this->wallet_balance + $amount;
            if ($newBalance > (float) $this->max_wallet) {
                throw new \RuntimeException("Plafond de {$this->max_wallet} FCFA dépassé.");
            }
            $balanceBefore = (float) $this->wallet_balance;
            $this->update(['wallet_balance' => $newBalance]);

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'admin_id'       => $adminId,
                'wallet'         => 'sales',
                'type'           => 'credit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'description'    => $description ?? 'Recharge admin',
                'reference'      => $reference,
            ]);

            return $newBalance;
        });
    }

    /**
     * Débite le portefeuille de vente pour un achat. Throws si solde insuffisant.
     */
    public function debit(float $amount, ?string $description = null, ?string $reference = null): float
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            $this->refresh();
            if ((float) $this->wallet_balance < $amount) {
                throw new \RuntimeException('Solde insuffisant.');
            }
            $balanceBefore = (float) $this->wallet_balance;
            $newBalance = $balanceBefore - $amount;
            $this->update(['wallet_balance' => $newBalance]);

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'description'    => $description ?? 'Achat client',
                'reference'      => $reference,
            ]);

            return $newBalance;
        });
    }

    /**
     * Verse une commission sur le portefeuille DÉDIÉ aux commissions
     * (séparé du portefeuille de vente — voir migration 2026_05_03_205305).
     */
    public function commission(float $amount, ?string $description = null, ?string $reference = null): float
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            $this->refresh();
            $balanceBefore = (float) $this->commission_balance;
            $newBalance = $balanceBefore + $amount;
            $this->update([
                'commission_balance'      => $newBalance,
                'total_commission_earned' => $this->total_commission_earned + $amount,
            ]);

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'commission',
                'type'           => 'commission',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'description'    => $description ?? 'Commission sur vente',
                'reference'      => $reference,
            ]);

            return $newBalance;
        });
    }

    public function getWalletPercentageAttribute(): float
    {
        return (float) $this->max_wallet > 0
            ? round(((float) $this->wallet_balance / (float) $this->max_wallet) * 100, 1)
            : 0;
    }

    /**
     * Marque que le vendeur a encaissé X FCFA cash physiquement (lors de la
     * confirmation d'une vente cash). Cet argent ne lui appartient pas — il
     * doit être restitué à KardAfrica via E-Billing pour reconstituer le float.
     */
    public function recordCashCollection(float $amount, ?string $reference = null): float
    {
        if ($amount <= 0) return (float) $this->cash_to_remit;

        return DB::transaction(function () use ($amount, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();
            $newCash = (float) $fresh->cash_to_remit + $amount;
            $fresh->cash_to_remit = $newCash;
            $fresh->save();

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'cash_collected',
                'amount'         => $amount,
                'balance_before' => (float) $fresh->cash_to_remit - $amount,
                'balance_after'  => $newCash,
                'description'    => 'Cash encaissé chez le client (à reverser)',
                'reference'      => $reference,
            ]);

            $this->refresh();
            return $newCash;
        });
    }

    /**
     * Validation d'une remise cash via E-Billing : décrémente cash_to_remit
     * ET reconstitue wallet_balance du même montant. Le float est ainsi
     * reconstitué sans intervention manuelle du gérant.
     */
    public function confirmCashRemittance(float $amount, ?string $reference = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($amount, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            if ((float) $fresh->cash_to_remit < $amount) {
                throw new \RuntimeException('Le montant à remettre dépasse le cash dû.');
            }
            // Plafonné par max_wallet : on n'autorise pas wallet_balance > max_wallet
            $newWalletRaw = (float) $fresh->wallet_balance + $amount;
            if ($newWalletRaw > (float) $fresh->max_wallet) {
                throw new \RuntimeException("La remise dépasserait le plafond du wallet (" . number_format($fresh->max_wallet, 0, ',', ' ') . " FCFA).");
            }

            $balanceBefore = (float) $fresh->wallet_balance;
            $newWallet     = $newWalletRaw;
            $newCashToRemit = max(0, (float) $fresh->cash_to_remit - $amount);

            $fresh->wallet_balance = $newWallet;
            $fresh->cash_to_remit  = $newCashToRemit;
            $fresh->save();

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'cash_remittance',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newWallet,
                'description'    => 'Remise cash via E-Billing — float reconstitué',
                'reference'      => $reference,
            ]);

            $this->refresh();
            return [
                'wallet_balance' => $newWallet,
                'cash_to_remit'  => $newCashToRemit,
            ];
        });
    }

    /**
     * Solde réellement disponible = wallet de vente − montants réservés (cash en attente).
     */
    public function getAvailableBalanceAttribute(): float
    {
        return max(0, (float) $this->wallet_balance - (float) $this->wallet_locked);
    }

    /**
     * Total cumulé du cash physiquement encaissé depuis l'inscription
     * (somme de toutes les txs type='cash_collected'). Indicateur historique
     * — ne diminue jamais, contrairement à `cash_to_remit` qui se solde au
     * fil des remises E-Billing.
     */
    public function getTotalCashCollectedAttribute(): float
    {
        return (float) $this->walletTransactions()
            ->where('type', 'cash_collected')
            ->sum('amount');
    }

    /**
     * Total déjà reversé via E-Billing (ce que le vendeur a remis à KardAfrica).
     */
    public function getTotalCashRemittedAttribute(): float
    {
        return (float) $this->walletTransactions()
            ->where('type', 'cash_remittance')
            ->sum('amount');
    }

    /**
     * Réserve un montant sur le wallet de vente pour une commande cash en attente.
     * Échoue si le solde dispo est insuffisant ; n'altère PAS wallet_balance.
     */
    public function lockFunds(float $amount, ?string $reference = null): float
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($amount, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();
            $available = (float) $fresh->wallet_balance - (float) $fresh->wallet_locked;
            if ($available < $amount) {
                throw new \RuntimeException('Solde disponible insuffisant pour bloquer ce montant.');
            }
            $fresh->wallet_locked = (float) $fresh->wallet_locked + $amount;
            $fresh->save();
            $this->refresh();
            return (float) $this->wallet_locked;
        });
    }

    /**
     * Libère un montant précédemment bloqué (commande annulée ou expirée).
     */
    public function releaseFunds(float $amount, ?string $reference = null): float
    {
        if ($amount <= 0) return (float) $this->wallet_locked;

        return DB::transaction(function () use ($amount) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();
            $newLocked = max(0, (float) $fresh->wallet_locked - $amount);
            $fresh->wallet_locked = $newLocked;
            $fresh->save();
            $this->refresh();
            return $newLocked;
        });
    }

    /**
     * Débit final d'un montant qui était bloqué : retire à la fois du wallet_balance
     * ET du wallet_locked, puis enregistre une transaction. Atomique.
     */
    public function debitLocked(float $amount, ?string $description = null, ?string $reference = null): float
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();
            if ((float) $fresh->wallet_balance < $amount) {
                throw new \RuntimeException('Solde insuffisant.');
            }
            $balanceBefore = (float) $fresh->wallet_balance;
            $newBalance = $balanceBefore - $amount;
            $newLocked  = max(0, (float) $fresh->wallet_locked - $amount);
            $fresh->wallet_balance = $newBalance;
            $fresh->wallet_locked  = $newLocked;
            $fresh->save();

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'description'    => $description ?? 'Encaissement cash',
                'reference'      => $reference,
            ]);

            $this->refresh();
            return $newBalance;
        });
    }
}
