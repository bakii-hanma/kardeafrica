<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ResellerOrder;

/**
 * RefundPhone
 * ===========
 * Résout le MSISDN de destination d'un remboursement.
 *
 * Deux choix proposés à l'utilisateur :
 *   - « numéro du compte » : le téléphone enregistré sur le compte (users.phone
 *     / user_profile.phone), repli sur billing_details.phone (numéro du paiement) ;
 *     pour une commande vendeur, le numéro de téléphone du client saisi à la vente ;
 *   - « autre numéro » : saisie libre (indicatif + national).
 *
 * Le résultat est toujours ramené en forme E.164 sans « + » (241XXXXXXXX),
 * comme l'attend l'API E-Billing / WHAPI.
 */
class RefundPhone
{
    public const MODE_ACCOUNT = 'account';
    public const MODE_OTHER   = 'other';

    /**
     * Résout la destination d'un remboursement de commande client (Order).
     *
     * @param Request|array $input  champs refund_phone_mode / _country / _national
     * @return array{msisdn:?string, operator:?string, mode:string}
     */
    public static function resolve(Order $order, $input): array
    {
        return self::resolveFor(self::accountPhone($order), $input);
    }

    /**
     * Résout la destination d'un remboursement de commande vendeur (ResellerOrder).
     * « Numéro du compte » = customer_phone de la vente.
     *
     * @param Request|array $input  champs refund_phone_mode / _country / _national
     * @return array{msisdn:?string, operator:?string, mode:string}
     */
    public static function resolveReseller(ResellerOrder $order, $input): array
    {
        return self::resolveFor(self::resellerCustomerPhone($order), $input);
    }

    /**
     * Cœur du resolver : part d'un numéro de compte donné (éventuellement null)
     * et applique le choix de l'utilisateur.
     *
     * @param Request|array $input  champs refund_phone_mode / _country / _national
     * @return array{msisdn:?string, operator:?string, mode:string}
     */
    public static function resolveFor(?string $accountPhone, $input): array
    {
        if (is_array($input)) {
            $mode    = (string) ($input['refund_phone_mode'] ?? self::MODE_ACCOUNT);
            $country = (string) ($input['refund_phone_country'] ?? '');
            $national = (string) ($input['refund_phone_national'] ?? '');
        } else {
            $mode    = (string) ($input->input('refund_phone_mode', self::MODE_ACCOUNT));
            $country = (string) ($input->input('refund_phone_country', ''));
            $national = (string) $input->input('refund_phone_national', '');
        }

        if ($mode === self::MODE_OTHER) {
            $msisdn = DialCodes::compose($country, $national);
        } else {
            $msisdn = $accountPhone;
        }

        $msisdn = $msisdn !== null ? Phone::normalize($msisdn) : null;

        return [
            'msisdn'   => $msisdn,
            'operator' => $msisdn !== null ? PhoneOperator::label($msisdn) : null,
            'mode'     => $mode === self::MODE_OTHER ? self::MODE_OTHER : self::MODE_ACCOUNT,
        ];
    }

    /** Numéro de compte du client : users.phone, repli profile.phone puis billing_details.phone. */
    public static function accountPhone(Order $order): ?string
    {
        $user = $order->user;

        // 1. Téléphone du compte (création du compte).
        if ($user && Phone::isUsableAsKey($user->phone)) {
            return Phone::normalize($user->phone);
        }
        if ($user && $user->profile && Phone::isUsableAsKey($user->profile->phone)) {
            return Phone::normalize($user->profile->phone);
        }

        // 2. Repli : numéro utilisé au paiement.
        return Phone::normalize((string) data_get($order->billing_details, 'phone'));
    }

    /** Numéro de compte prêt à l'affichage (ou null). */
    public static function displayAccountPhone(Order $order): ?string
    {
        $n = self::accountPhone($order);

        return $n !== null ? Phone::display($n) : null;
    }

    /** Numéro du client d'une commande vendeur (customer_phone), normalisé. */
    public static function resellerCustomerPhone(ResellerOrder $order): ?string
    {
        return Phone::normalize((string) $order->customer_phone);
    }

    /** Numéro du client d'une commande vendeur, prêt à l'affichage (ou null). */
    public static function displayResellerCustomerPhone(ResellerOrder $order): ?string
    {
        $n = self::resellerCustomerPhone($order);

        return $n !== null ? Phone::display($n) : null;
    }
}