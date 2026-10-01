<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Integration;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    public function testIntegreNormaliseEtRefuse(): void
    {
        $r = Integration::integrer(
            ['Already' => 'Déjà'],
            [
                ['cle' => 'Your cart', 'traduction' => "Votre panier ...", 'module' => 'Amasty_Cart', 'zone' => 'front'],
                ['cle' => 'Empty', 'traduction' => '', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
                ['cle' => 'PayPal', 'traduction' => '=', 'module' => 'Amasty_Cart', 'zone' => 'front'],
                ['cle' => 'In core', 'traduction' => 'Dans le cœur', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
                ['cle' => 'Community', 'traduction' => 'Communauté', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
            ],
            ['In core' => 'core.csv'],
            ['Community' => 'Communautaire']
        );

        self::assertSame(['Already' => 'Déjà', 'Your cart' => 'Votre panier …'], $r['entrees']);
        self::assertSame(['Your cart'], $r['front']);
        self::assertSame(
            [['cle' => 'In core', 'raison' => 'déjà dans core.csv'], ['cle' => 'Community', 'raison' => 'traduite par le pack communautaire']],
            $r['refusees']
        );
    }
}
