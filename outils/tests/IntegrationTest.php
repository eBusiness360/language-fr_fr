<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Integration;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    public function testLaTraductionReprendLesEspacesDeBordDeLaCle(): void
    {
        // Fragments recolles a d'autres chaines : l'espace de bord fait partie du texte.
        $r = Integration::integrer(
            [],
            [
                ['cle' => ' each and ', 'traduction' => ' l’unité et', 'module' => 'Bss_X', 'zone' => 'front'],
                ['cle' => 'Order Status: ', 'traduction' => 'Statut de la commande :', 'module' => 'Bss_X', 'zone' => 'front'],
                ['cle' => 'Buy %1 for', 'traduction' => 'Achetez-en %1 à ', 'module' => 'Bss_X', 'zone' => 'front'],
            ],
            [],
            []
        );

        self::assertSame(' l’unité et ', $r['entrees'][' each and ']);
        self::assertSame("Statut de la commande\u{00A0}: ", $r['entrees']['Order Status: ']);
        self::assertSame('Achetez-en %1 à', $r['entrees']['Buy %1 for']);
    }

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
