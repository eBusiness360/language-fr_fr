<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Langue;
use PHPUnit\Framework\TestCase;

final class LangueTest extends TestCase
{
    public function testLeFichierDuDepotEstCoherent(): void
    {
        $l = Langue::charger();
        self::assertSame('fr_FR', $l->locale);
        self::assertSame('maxcode_fr_fr', $l->composant);
        self::assertSame('maxcode/language-fr_fr', $l->paquet);
        self::assertSame('community-engineering/language-fr_fr', $l->paquetCommunautaire);
        self::assertSame('fr', $l->typographie);
        // Le nom du composant suit la convention Magento <vendor>_<package>
        // et le package reprend la locale en minuscules.
        self::assertStringEndsWith('_' . strtolower($l->locale), $l->composant);
    }

    public function testUnFichierIncompletEstRefuse(): void
    {
        $f = sys_get_temp_dir() . '/langue-' . uniqid() . '.json';
        file_put_contents($f, '{"locale": "de_DE"}');
        try {
            $this->expectException(\RuntimeException::class);
            Langue::charger($f);
        } finally {
            unlink($f);
        }
    }
}
