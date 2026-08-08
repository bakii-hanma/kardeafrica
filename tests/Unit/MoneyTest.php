<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Cœur du calcul de prix (C1) : conversion devise → FCFA + arrondi au palier.
 * C'est ce calcul qui fait autorité sur le prix payé — un bug ici = mauvais
 * montant facturé. Tests sans DB : on injecte les taux/palier via le memo
 * (comme s'ils venaient de la config admin) pour rester déterministes.
 */
class MoneyTest extends TestCase
{
    /** Injecte des taux + palier fixes dans le memo statique (pas de DB). */
    private function primeRates(array $rates, int $step): void
    {
        $ref = new ReflectionClass(Money::class);
        $r = $ref->getProperty('memoRates');
        $r->setAccessible(true);
        $r->setValue(null, $rates);
        $s = $ref->getProperty('memoStep');
        $s->setAccessible(true);
        $s->setValue(null, $step);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Taux et palier de production (EUR = 750, arrondi 200).
        $this->primeRates(['XAF' => 1, 'XOF' => 1, 'EUR' => 750, 'USD' => 620], 200);
    }

    protected function tearDown(): void
    {
        Money::resetMemo();
        parent::tearDown();
    }

    public function test_xaf_est_inchange_hors_arrondi(): void
    {
        $this->assertSame(5000, Money::toFcfa(5000, 'XAF'));
        $this->assertSame(1000, Money::toFcfa(1000, 'XOF'));
    }

    public function test_arrondi_vers_le_haut_au_palier(): void
    {
        // step = 200 → 5001 → 5200, 5200 reste 5200
        $this->assertSame(5200, Money::toFcfa(5001, 'XAF'));
        $this->assertSame(5200, Money::toFcfa(5200, 'XAF'));
        $this->assertSame(5200, Money::toFcfa(5199, 'XAF'));
    }

    public function test_conversion_eur_correspond_aux_prix_affiches(): void
    {
        // Prix réels du catalogue (PSN France) : le montant natif discounté × 750,
        // arrondi à 200, doit donner exactement les prix affichés sur le site.
        $this->assertSame(7200, Money::toFcfa(9.4245, 'EUR'));   // PSN FR 10 EUR
        $this->assertSame(14200, Money::toFcfa(18.849, 'EUR'));  // PSN FR 20 EUR
        $this->assertSame(17800, Money::toFcfa(23.56125, 'EUR')); // PSN FR 25 EUR
    }

    public function test_devise_inconnue_taux_1(): void
    {
        // Devise absente du memo → taux 1 (pas de crash), juste arrondi.
        $this->assertSame(3000, Money::toFcfa(2900, 'ZZZ'));
    }

    public function test_format_fcfa(): void
    {
        $this->assertSame('7 200 FCFA', Money::formatFcfa(9.4245, 'EUR'));
    }
}
