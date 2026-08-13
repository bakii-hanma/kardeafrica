<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    // ---- Carte Gabon ----
    // Les cartes locales sont désormais un catalogue admin global (aucun lien
    // reseller). Seuls les employés caissiers (MerchantUser) restent rattachés
    // à un reseller pour le futur scan au comptoir.

    public function merchantUsers()
    {
        return $this->hasMany(MerchantUser::class);
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
            // Verrou pessimiste (race-safe) : le contrôle de plafond et le calcul
            // se font sur la ligne fraîchement verrouillée, pas sur l'instance mémoire.
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            $balanceBefore = (float) $fresh->wallet_balance;
            $newBalance    = $balanceBefore + $amount;
            if ($newBalance > (float) $fresh->max_wallet) {
                throw new \RuntimeException("Plafond de {$fresh->max_wallet} FCFA dépassé.");
            }
            $fresh->wallet_balance = $newBalance;
            $fresh->save();

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

            // Synchronise l'instance courante avec l'état persisté
            $this->refresh();
            return $newBalance;
        });
    }

    /**
     * Transfère des commissions gagnées vers le portefeuille de VENTE.
     *
     * Jusqu'ici `commission_balance` n'avait aucune sortie : aucune route,
     * aucun contrôleur, aucun job ne permettait d'en disposer. Le vendeur
     * accumulait un solde qu'il ne pouvait jamais toucher. Ce transfert lui
     * rend l'argent utile immédiatement — il le convertit en pouvoir de vente.
     *
     * Atomique et plafonné : impossible de transférer plus que le solde de
     * commissions, ni de dépasser le plafond de la cagnotte.
     *
     * @return array{commission_balance: float, wallet_balance: float}
     */
    public function transferCommissionToWallet(float $amount): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($amount) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            $commissionBefore = (float) $fresh->commission_balance;
            $walletBefore     = (float) $fresh->wallet_balance;

            if ($commissionBefore < $amount) {
                throw new \RuntimeException('Commissions insuffisantes.');
            }
            if ($walletBefore + $amount > (float) $fresh->max_wallet) {
                $place = max(0, (float) $fresh->max_wallet - $walletBefore);
                throw new \RuntimeException(
                    'Ta cagnotte ne peut accueillir que ' . number_format($place, 0, ',', ' ') . ' FCFA de plus.'
                );
            }

            $fresh->commission_balance = $commissionBefore - $amount;
            $fresh->wallet_balance     = $walletBefore + $amount;
            $fresh->save();

            // Deux écritures : la sortie du portefeuille commissions et l'entrée
            // dans celui de vente. L'historique reste lisible des deux côtés.
            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'commission',
                'type'           => 'transfer_out',
                'amount'         => $amount,
                'balance_before' => $commissionBefore,
                'balance_after'  => $commissionBefore - $amount,
                'description'    => 'Transfert vers le solde de vente',
            ]);
            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'transfer_in',
                'amount'         => $amount,
                'balance_before' => $walletBefore,
                'balance_after'  => $walletBefore + $amount,
                'description'    => 'Transfert depuis mes commissions',
            ]);

            $this->refresh();

            return [
                'commission_balance' => (float) $fresh->commission_balance,
                'wallet_balance'     => (float) $fresh->wallet_balance,
            ];
        });
    }

    /**
     * RESTITUE au portefeuille de vente un montant précédemment débité
     * (remboursement d'une commande non livrée).
     *
     * Contrairement à `credit()`, cette opération IGNORE le plafond : elle ne
     * fait qu'annuler un débit antérieur, elle n'ajoute pas de float neuf. Le
     * plafond encadre ce qu'un vendeur peut *charger*, pas ce qu'on lui *rend*.
     *
     * Sans cela, un remboursement pouvait échouer APRÈS le virement au client
     * (le contrôle de plafond levait une exception dans la transaction de
     * clôture) : le client était remboursé, le vendeur ne récupérait jamais son
     * argent, et chaque nouvel essai butait sur le même plafond.
     */
    public function refundCredit(float $amount, ?string $description = null, ?string $reference = null): float
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($amount, $description, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            $balanceBefore = (float) $fresh->wallet_balance;
            $newBalance    = $balanceBefore + $amount;

            $fresh->wallet_balance = $newBalance;
            // Le remboursement peut faire repasser au-dessus du plafond courant
            // (ex. le vendeur a rechargé depuis la vente). On le trace pour que
            // l'admin le voie, sans jamais bloquer la restitution.
            if ($newBalance > (float) $fresh->max_wallet) {
                Log::notice('Restitution au-dessus du plafond wallet', [
                    'reseller_id' => $this->id,
                    'new_balance' => $newBalance,
                    'max_wallet'  => (float) $fresh->max_wallet,
                    'reference'   => $reference,
                ]);
            }
            $fresh->save();

            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'refund',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'description'    => $description ?? 'Restitution remboursement',
                'reference'      => $reference,
            ]);

            $this->refresh();
            return $newBalance;
        });
    }

    /**
     * Débite le portefeuille de vente pour un achat. Throws si solde insuffisant.
     */
    public function debit(float $amount, ?string $description = null, ?string $reference = null): float
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            // Verrou pessimiste (race-safe) : le contrôle de solde se fait sur la
            // ligne fraîchement verrouillée — deux débits concurrents se sérialisent.
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            if ((float) $fresh->wallet_balance < $amount) {
                throw new \RuntimeException('Solde insuffisant.');
            }
            $balanceBefore = (float) $fresh->wallet_balance;
            $newBalance = $balanceBefore - $amount;
            $fresh->wallet_balance = $newBalance;
            $fresh->save();

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

            // Synchronise l'instance courante avec l'état persisté
            $this->refresh();
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
            // Verrou pessimiste (race-safe) : calcul sur la ligne fraîchement
            // verrouillée pour éviter la perte d'une commission concurrente.
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();

            $balanceBefore = (float) $fresh->commission_balance;
            $newBalance = $balanceBefore + $amount;
            $fresh->commission_balance      = $newBalance;
            $fresh->total_commission_earned = (float) $fresh->total_commission_earned + $amount;
            $fresh->save();

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

            // Synchronise l'instance courante avec l'état persisté
            $this->refresh();
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
     * Niveau d'alerte de la jauge de solde, pour la coloration du dashboard.
     * Sous 30 % du plafond le vendeur doit songer à recharger, sous 10 % il ne
     * peut quasiment plus vendre. Ici pour être testable sans passer par le HTML.
     *
     * @return 'ok'|'warn'|'danger'
     */
    public function walletTone(): string
    {
        $pct = $this->wallet_percentage;
        if ($pct < 10) return 'danger';
        if ($pct < 30) return 'warn';
        return 'ok';
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

            // Trace le blocage : sans elle, le vendeur voyait son solde
            // disponible baisser sans aucune ligne correspondante dans son
            // historique. Le solde du portefeuille ne bouge pas — seule la
            // part réservée change — d'où before == after.
            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'lock',
                'amount'         => $amount,
                'balance_before' => (float) $fresh->wallet_balance,
                'balance_after'  => (float) $fresh->wallet_balance,
                'description'    => 'Fonds réservés (vente en espèces en attente)',
                'reference'      => $reference,
            ]);

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

        return DB::transaction(function () use ($amount, $reference) {
            $fresh = static::where('id', $this->id)->lockForUpdate()->first();
            $newLocked = max(0, (float) $fresh->wallet_locked - $amount);
            $fresh->wallet_locked = $newLocked;
            $fresh->save();

            // Pendant du blocage : la libération doit être visible elle aussi.
            ResellerWalletTransaction::create([
                'reseller_id'    => $this->id,
                'wallet'         => 'sales',
                'type'           => 'unlock',
                'amount'         => $amount,
                'balance_before' => (float) $fresh->wallet_balance,
                'balance_after'  => (float) $fresh->wallet_balance,
                'description'    => 'Fonds réservés libérés (vente annulée ou expirée)',
                'reference'      => $reference,
            ]);

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
