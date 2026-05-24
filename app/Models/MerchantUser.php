<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/**
 * MerchantUser
 * ===
 * Employé d'un marchand autorisé à scanner/encaisser les cartes au comptoir.
 *
 * Le PIN 4 chiffres est hashé bcrypt (cf spec §SÉCURITÉ #5). Ne JAMAIS le
 * stocker en clair. Les méthodes setPin() / checkPin() gèrent ça.
 */
class MerchantUser extends Model
{
    use HasFactory;

    public const ROLE_OWNER   = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CASHIER = 'cashier';

    protected $fillable = [
        'reseller_id',
        'name', 'phone',
        'role',
        'pin_code_hash',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['pin_code_hash'];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ============================================================
    // Relations
    // ============================================================

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(MerchantCardRedemption::class);
    }

    // ============================================================
    // PIN handling — toujours via ces helpers, jamais en direct
    // ============================================================

    public function setPin(string $pin): void
    {
        $this->pin_code_hash = Hash::make($pin);
    }

    public function checkPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_code_hash);
    }
}
