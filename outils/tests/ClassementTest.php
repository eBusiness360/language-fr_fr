<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Classement;
use PHPUnit\Framework\TestCase;

final class ClassementTest extends TestCase
{
    public function testModulesEtThemes(): void
    {
        self::assertSame('core', Classement::editeur('Magento_Catalog'));
        self::assertSame('amasty', Classement::editeur('Amasty_Base'));
        self::assertSame('mageworx', Classement::editeur('MageWorx_SeoBase'));
        self::assertSame('bss', Classement::editeur('Bss_Gdpr'));
        self::assertSame('smartwave', Classement::editeur('frontend/Smartwave/porto_child'));
        self::assertSame('swissup', Classement::editeur('frontend/Swissup/breeze-evolution'));
        self::assertSame('core', Classement::editeur('adminhtml/Magento/backend'));
        self::assertSame('core', Classement::editeur('lib/web'));
    }

    public function testExclusionParPrefixe(): void
    {
        $p = ['Maxcode_', 'frontend/Totalinux/'];
        self::assertTrue(Classement::exclu('Maxcode_SupplierOrder', $p));
        self::assertTrue(Classement::exclu('frontend/Totalinux/default', $p));
        self::assertFalse(Classement::exclu('Amasty_Base', $p));
        self::assertFalse(Classement::exclu('frontend/Swissup/breeze', $p));
    }
}
