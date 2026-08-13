<?php

namespace App\Support;

/**
 * Statuts métier → ton visuel et libellé français.
 *
 * Le mapping vivait en dur dans `components/ui/pill.blade.php` (P2). Extrait
 * ici parce que trois consommateurs en ont besoin : la pill elle-même, les
 * onglets des écrans liste, et les tests qui doivent couvrir TOUS les statuts
 * réels des modèles.
 *
 * Les statuts couverts sont ceux réellement déclarés par Order, Payment,
 * UserCard, MerchantCardPurchase et ResellerOrder — pas une liste inventée.
 * Un statut inconnu retombe sur un ton neutre plutôt que de casser la page :
 * un écran qui plante parce qu'un statut a été ajouté en base est pire qu'un
 * badge gris.
 */
class AdminStatus
{
    /** @var array<string, array{tone:string, label:string}> */
    public const MAP = [
        // ---- Cycle de vie d'une commande ----
        'pending'        => ['tone' => 'pending', 'label' => 'En attente'],
        'processing'     => ['tone' => 'info',    'label' => 'En cours'],
        'shipped'        => ['tone' => 'info',    'label' => 'Expédiée'],
        'delivered'      => ['tone' => 'ok',      'label' => 'Livrée'],
        'completed'      => ['tone' => 'ok',      'label' => 'Terminée'],
        'cancelled'      => ['tone' => 'muted',   'label' => 'Annulée'],
        'refunding'      => ['tone' => 'special', 'label' => 'Remb. en cours'],
        'refunded'       => ['tone' => 'special', 'label' => 'Remboursée'],
        'failed'         => ['tone' => 'danger',  'label' => 'Échec'],

        // ---- Paiement ----
        'paid'           => ['tone' => 'ok',      'label' => 'Payé'],

        // ---- Cartes ----
        'active'         => ['tone' => 'ok',      'label' => 'Active'],
        'inactive'       => ['tone' => 'muted',   'label' => 'Inactive'],
        'used'           => ['tone' => 'muted',   'label' => 'Utilisée'],
        'expired'        => ['tone' => 'muted',   'label' => 'Expirée'],
        'partially_used' => ['tone' => 'info',    'label' => 'Utilisée en partie'],
        'fully_used'     => ['tone' => 'muted',   'label' => 'Épuisée'],
    ];

    public const NEUTRAL = 'muted';

    /** Ton visuel d'un statut ; neutre si inconnu. */
    public static function tone(?string $status): string
    {
        return self::MAP[(string) $status]['tone'] ?? self::NEUTRAL;
    }

    /**
     * Libellé français. Un statut absent du mapping est rendu tel quel,
     * capitalisé : mieux vaut afficher la valeur brute qu'un vide.
     */
    public static function label(?string $status): string
    {
        $s = (string) $status;

        return self::MAP[$s]['label'] ?? ($s === '' ? '—' : ucfirst(str_replace('_', ' ', $s)));
    }

    public static function isKnown(?string $status): bool
    {
        return array_key_exists((string) $status, self::MAP);
    }
}
