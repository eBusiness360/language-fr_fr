<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Typographie;
use PHPUnit\Framework\TestCase;

final class TypographieTest extends TestCase
{
    private const N = "\u{00A0}";

    /** @return list<string> */
    private function regles(string $t): array
    {
        return array_column(Typographie::erreurs($t), 'regle');
    }

    public function testUnTexteConformeNaAucuneErreur(): void
    {
        self::assertSame([], $this->regles('L’article est ajouté' . self::N . ': «' . self::N . 'ok' . self::N . '»' . self::N . '!'));
    }

    public function testApostropheDroiteRefuseeMemeDansUneBalise(): void
    {
        self::assertSame(['apostrophe'], $this->regles("L'article"));
        self::assertSame(['apostrophe'], $this->regles("<a href='x'>lien</a>"));
    }

    public function testEspaceNormaleAvantPonctuationDouble(): void
    {
        self::assertSame(['espace-ponctuation'], $this->regles('Total : 5'));
    }

    public function testPonctuationCollee(): void
    {
        self::assertSame(['espace-ponctuation'], $this->regles('Total: 5'));
        self::assertSame(['espace-ponctuation'], $this->regles('Vraiment?'));
    }

    public function testCeQuiNEstPasDuTexteEchappeAuxRegles(): void
    {
        self::assertSame([], $this->regles('Ouvert de 10:30 à 18:00'));
        self::assertSame([], $this->regles('Voir https://exemple.fr?a=b'));
        self::assertSame([], $this->regles('<span style="color:red">Rouge</span>'));
        self::assertSame([], $this->regles('{{var order.getId()}}&nbsp;commande'));
    }

    public function testGuillemetsDroitsHorsBalisage(): void
    {
        self::assertSame(['guillemets'], $this->regles('Cliquez sur "Envoyer"'));
    }

    public function testGuillemetsFrancaisSansInsecable(): void
    {
        self::assertSame(['guillemets'], $this->regles('« Envoyer »'));
    }

    public function testPointsDeSuspension(): void
    {
        self::assertSame(['suspension'], $this->regles('Chargement...'));
    }

    public function testTutoiementSignale(): void
    {
        self::assertSame(['tutoiement'], array_column(Typographie::avertissements('Ton panier est vide'), 'regle'));
        self::assertSame([], Typographie::avertissements('Votre panier est vide'));
    }

    public function testMajusculesDeTitreSignalees(): void
    {
        self::assertSame(['majuscules'], array_column(Typographie::avertissements('Ajouter Au Panier Rapide'), 'regle'));
        self::assertSame([], Typographie::avertissements('Ajouter au panier PayPal'));
    }
}
