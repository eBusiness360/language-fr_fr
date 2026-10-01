<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Normaliseur;
use Maxcode\LanguagePack\Outils\Typographie;
use PHPUnit\Framework\TestCase;

final class NormaliseurTest extends TestCase
{
    private const N = "\u{00A0}";

    public function testCorrigeLeTexteVisible(): void
    {
        self::assertSame('L’article' . self::N . ': ok' . self::N . '!', Normaliseur::normaliser("L'article : ok!"));
        self::assertSame('Chargement…', Normaliseur::normaliser('Chargement...'));
        self::assertSame('Cliquez sur «' . self::N . 'Envoyer' . self::N . '»', Normaliseur::normaliser('Cliquez sur "Envoyer"'));
        self::assertSame('«' . self::N . 'Envoyer' . self::N . '»', Normaliseur::normaliser('« Envoyer »'));
    }

    public function testNeTouchePasAuBalisageNiAuxDirectives(): void
    {
        $s = '<a href="x" style="color:red">Lien</a> {{var a}} 10:30';
        self::assertSame($s, Normaliseur::normaliser($s));
    }

    public function testLeResultatPasseLesControles(): void
    {
        self::assertSame([], Typographie::erreurs(Normaliseur::normaliser("Vraiment? L'aide... \"ici\" : oui")));
    }

    public function testIdempotent(): void
    {
        $une = Normaliseur::normaliser("L'aide : « ok »...");
        self::assertSame($une, Normaliseur::normaliser($une));
    }
}
