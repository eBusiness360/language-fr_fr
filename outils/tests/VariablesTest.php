<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Variables;
use PHPUnit\Framework\TestCase;

final class VariablesTest extends TestCase
{
    public function testExtraitParametresDirectivesEtBalises(): void
    {
        self::assertSame(
            ['%1', '%s', '</a', '<a', '{{var name}}'],
            Variables::extraire('%1 <a href="x">%s</a> {{var name}}')
        );
    }

    public function testLOrdreNeComptePasMaisLeNombreSi(): void
    {
        self::assertSame(Variables::extraire('%2 de %1'), Variables::extraire('%1 of %2'));
        self::assertNotSame(Variables::extraire('%1'), Variables::extraire('%1 %1'));
    }

    public function testLesAttributsDesBalisesNeComptentPas(): void
    {
        self::assertSame(Variables::extraire('<b class="x">a</b>'), Variables::extraire('<B>a</b>'));
    }
}
