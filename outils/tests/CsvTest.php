<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Csv;
use PHPUnit\Framework\TestCase;

final class CsvTest extends TestCase
{
    private string $fichier;

    protected function setUp(): void
    {
        $this->fichier = sys_get_temp_dir() . '/csv-' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->fichier);
    }

    public function testEcrireTrieQuoteToutEtRelitALIdentique(): void
    {
        Csv::ecrire($this->fichier, [
            'Zebra' => 'Zèbre',
            'Say "hi"' => 'Dites « bonjour »',
            '404' => 'Introuvable',
            'Active filters\\:' => 'Filtres actifs :',
        ]);

        $brut = (string) file_get_contents($this->fichier);
        self::assertSame(
            "\"404\",\"Introuvable\"\n"
            . "\"Active filters\\:\",\"Filtres actifs :\"\n"
            . "\"Say \"\"hi\"\"\",\"Dites « bonjour »\"\n"
            . "\"Zebra\",\"Zèbre\"\n",
            $brut
        );

        $relu = Csv::lire($this->fichier);
        self::assertCount(4, $relu);
        self::assertSame('Say "hi"', $relu[2]['cle']);
        self::assertSame(3, $relu[2]['ligne']);
        self::assertSame(2, $relu[2]['champs']);
        self::assertSame('Active filters\\:', $relu[1]['cle']);
    }

    public function testLireCompteLesChampsEtIgnoreLesLignesVides(): void
    {
        file_put_contents($this->fichier, "\"a\",\"b\",\"c\"\n\n\"d\",\"e\"\n");
        $relu = Csv::lire($this->fichier);
        self::assertSame(3, $relu[0]['champs']);
        self::assertSame(3, $relu[1]['ligne']);
    }
}
