<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Lecture et ecriture des CSV de traduction au format de Magento : virgule,
 * guillemets doubles, AUCUN caractere d'echappement (les antislashs sont
 * litteraux, comme dans Magento\Framework\File\Csv).
 */
final class Csv
{
    /**
     * @return list<array{cle: string, traduction: string, ligne: int, champs: int}>
     */
    public static function lire(string $fichier): array
    {
        $h = fopen($fichier, 'rb');
        if ($h === false) {
            throw new \RuntimeException("Lecture impossible : $fichier");
        }
        $entrees = [];
        $n = 0;
        while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $n++;
            if ($l === [null] || $l === []) {
                continue;
            }
            $entrees[] = [
                'cle' => (string) $l[0],
                'traduction' => (string) ($l[1] ?? ''),
                'ligne' => $n,
                'champs' => count($l),
            ];
        }
        fclose($h);

        return $entrees;
    }

    /**
     * @param array<array-key, string> $entrees cle => traduction
     */
    public static function ecrire(string $fichier, array $entrees): void
    {
        // Les cles numeriques (« 404 ») deviennent des entiers en PHP : on
        // compare et on ecrit toujours des chaines.
        uksort($entrees, static fn ($a, $b): int => strcmp((string) $a, (string) $b));
        $lignes = '';
        foreach ($entrees as $cle => $traduction) {
            $lignes .= self::champ((string) $cle) . ',' . self::champ($traduction) . "\n";
        }
        if (file_put_contents($fichier, $lignes) === false) {
            throw new \RuntimeException("Ecriture impossible : $fichier");
        }
    }

    private static function champ(string $valeur): string
    {
        return '"' . str_replace('"', '""', $valeur) . '"';
    }
}
