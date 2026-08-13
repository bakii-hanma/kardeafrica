<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un versement effectué à un commerçant.
 *
 * Enregistré à la main par un administrateur : les reversements passent par
 * Mobile Money ou en espèces, hors de l'application. La table n'automatise rien,
 * elle rend le solde vérifiable — ce qui manquait entièrement.
 */
class MerchantSettlement extends Model
{
    public const METHODS = [
        'mobile_money' => 'Mobile Money',
        'especes'      => 'Espèces',
        'virement'     => 'Virement bancaire',
    ];

    protected $fillable = [
        'card_owner_id', 'amount', 'method', 'reference', 'notes', 'recorded_by', 'settled_at',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(CardOwner::class, 'card_owner_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
