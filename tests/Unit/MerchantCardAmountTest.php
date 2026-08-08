<?php

namespace Tests\Unit;

use App\Models\MerchantCard;
use App\Support\MerchantCardCode;
use PHPUnit\Framework\TestCase;

/**
 * Anti-fraude C2 : le montant d'une carte marchand (= solde encaissable) doit
 * être ACHETABLE pour la carte. On teste la règle métier sans DB en construisant
 * des MerchantCard en mémoire (aucun save).
 */
class MerchantCardAmountTest extends TestCase
{
    private function card(array $attrs): MerchantCard
    {
        return new MerchantCard(array_merge([
            'is_active'           => true,
            'allow_custom_amount' => false,
            'denominations'       => [],
            'min_amount'          => null,
            'max_amount'          => null,
        ], $attrs));
    }

    public function test_denomination_listee_acceptee(): void
    {
        $card = $this->card(['denominations' => [5000, 10000, 25000, 50000]]);
        $this->assertTrue($card->isValidAmount(5000));
        $this->assertTrue($card->isValidAmount(50000));
    }

    public function test_montant_hors_denomination_refuse_sans_custom(): void
    {
        $card = $this->card(['denominations' => [5000, 10000]]);
        $this->assertFalse($card->isValidAmount(7000));
        $this->assertFalse($card->isValidAmount(500000)); // la fraude classique
    }

    public function test_montant_libre_dans_la_plage(): void
    {
        $card = $this->card([
            'allow_custom_amount' => true,
            'min_amount'          => 2000,
            'max_amount'          => 50000,
        ]);
        $this->assertTrue($card->isValidAmount(3000));
        $this->assertTrue($card->isValidAmount(2000));
        $this->assertTrue($card->isValidAmount(50000));
        $this->assertFalse($card->isValidAmount(1999));   // sous le min
        $this->assertFalse($card->isValidAmount(50001));  // au-dessus du max
    }

    public function test_montant_nul_ou_negatif_refuse(): void
    {
        $card = $this->card(['denominations' => [5000]]);
        $this->assertFalse($card->isValidAmount(0));
        $this->assertFalse($card->isValidAmount(-5000));
    }

    public function test_carte_inactive_refusee_a_la_vente_mais_livrable(): void
    {
        $card = $this->card(['denominations' => [5000], 'is_active' => false]);
        // À la vente (requireActive = true) : refusée.
        $this->assertFalse($card->isValidAmount(5000, true));
        // À la livraison d'une commande déjà payée (requireActive = false) : OK
        // si le montant est valide (la carte a pu être désactivée depuis l'achat).
        $this->assertTrue($card->isValidAmount(5000, false));
    }

    public function test_authoritative_amount_parse_et_valide(): void
    {
        // product_id non marchand → null
        $this->assertNull(MerchantCardCode::authoritativeAmount('1293556'));
        // format invalide → null
        $this->assertNull(MerchantCardCode::authoritativeAmount('merchant_'));
        $this->assertNull(MerchantCardCode::authoritativeAmount('merchant_5'));
    }
}
