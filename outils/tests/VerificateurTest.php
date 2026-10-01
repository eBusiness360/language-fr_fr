<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Verificateur;
use PHPUnit\Framework\TestCase;

final class VerificateurTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir() . '/pack-' . uniqid();
        mkdir($this->dossier);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dossier . '/*') ?: []);
        rmdir($this->dossier);
    }

    private function fichier(string $nom, string $contenu): string
    {
        file_put_contents($this->dossier . '/' . $nom, $contenu);

        return $this->dossier . '/' . $nom;
    }

    private function verificateur(): Verificateur
    {
        return new Verificateur(
            ['Translated by community' => 'Traduit par la communauté', 'Untranslated' => 'Untranslated'],
            ['Manage Gallery'],
            ['PayPal'],
            ['shopping cart' => 'panier']
        );
    }

    /** @return list<string> */
    private function regles(array $fichiers, string $niveau = 'erreur'): array
    {
        $r = [];
        foreach ($this->verificateur()->verifierPack($fichiers) as $c) {
            if ($c->niveau === $niveau) {
                $r[] = $c->regle;
            }
        }
        sort($r);

        return $r;
    }

    public function testUnPackConformeNaAucuneErreur(): void
    {
        $f = $this->fichier('core.csv', "\"Untranslated\",\"Non traduit\"\n\"PayPal\",\"PayPal\"\n");
        self::assertSame([], $this->regles(['core.csv' => $f]));
    }

    public function testLesErreursDeContenu(): void
    {
        $f = $this->fichier('core.csv',
            "\"Translated by community\",\"Autre chose\"\n"   // communautaire
            . "\"Same\",\"Same\"\n"                           // identique
            . "\"Empty\",\"\"\n"                              // vide
            . "\"Manage Gallery\",\"Gérer la galerie\"\n"     // interdite
            . "\"%1 items\",\"articles\"\n"                   // variables
            . "\"A\",\"B\",\"C\"\n"                           // format
        );
        self::assertSame(
            ['communautaire', 'format', 'identique', 'interdite', 'variables', 'vide'],
            $this->regles(['core.csv' => $f])
        );
    }

    public function testDoublonDansUnFichierEtEntreFichiers(): void
    {
        $a = $this->fichier('amasty.csv', "\"Hello\",\"Bonjour\"\n\"Hello\",\"Salut\"\n");
        $b = $this->fichier('core.csv', "\"Hello\",\"Bonjour\"\n");
        self::assertSame(['doublon', 'doublon-fichiers'], $this->regles(['amasty.csv' => $a, 'core.csv' => $b]));
    }

    public function testLeFichierLuiMeme(): void
    {
        $bom = $this->fichier('core.csv', "\xEF\xBB\xBF\"A\",\"B\"\r\n");    // BOM + \r
        self::assertSame(['fichier', 'fichier'], $this->regles(['core.csv' => $bom]));
        $sansFin = $this->fichier('amasty.csv', '"C","D"');               // pas de \n final
        self::assertSame(['fichier'], $this->regles(['amasty.csv' => $sansFin]));
    }

    public function testTypographieEtGlossaire(): void
    {
        $f = $this->fichier('core.csv', "\"Shopping cart\",\"L'endroit\"\n");
        self::assertSame(['apostrophe'], $this->regles(['core.csv' => $f]));
        self::assertSame(['glossaire'], $this->regles(['core.csv' => $f], 'avertissement'));
    }

    public function testLeGlossaireIgnoreLesVariables(): void
    {
        $f = $this->fichier('core.csv', "\"Hello %shopping_cart_name\",\"Bonjour %shopping_cart_name\"\n");
        self::assertSame([], $this->regles(['core.csv' => $f], 'avertissement'));
    }
}
