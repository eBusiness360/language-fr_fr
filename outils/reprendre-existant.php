<?php
declare(strict_types=1);

/**
 * Reprise unique de l'existant : pack Ambiance (751, sur-ensemble de ttlx),
 * AmastyTranslation, MageplazaTranslation et le CSV de CoreTranslation.
 * Chaque cle est rangee d'apres l'index d'extraction ; a defaut, d'apres sa
 * source. Les cles traduites par le communautaire sont ecartees.
 * Usage : outils/php outils/reprendre-existant.php <dossier-des-sources>
 * (le dossier contient : pack.csv, amasty.csv, mageplaza.csv, core.csv)
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Normaliseur;

$sources = rtrim($argv[1] ?? '', '/');
$racine = dirname(__DIR__);
$index = [];
$h = fopen(__DIR__ . '/a-traduire/index.csv', 'rb');
while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
    if ($l !== [null] && count($l) >= 4) {
        $index[$l[0]] = $l[2];
    }
}
fclose($h);
$communautaire = [];
foreach (Csv::lire(__DIR__ . '/.cache/communautaire.csv') as $e) {
    $communautaire[$e['cle']] = $e['traduction'];
}

$liste = static fn (string $f): array => array_values(array_filter(
    array_map('trim', file(__DIR__ . '/' . $f) ?: []),
    static fn (string $l): bool => $l !== '' && $l[0] !== '#'
));
$interdites = array_flip($liste('interdites.txt'));
// Cles portees par un module Maxcode (son propre i18n) : hors du pack.
$horsPack = ['Ref.' => 'Maxcode_SupplierOrder'];

$parFichier = [];
$rangee = [];    // cle => editeur deja choisi : une cle, un fichier
$ecartees = [];
$sansEffet = [];
$inconnues = [];
foreach (['pack' => 'core', 'amasty' => 'amasty', 'mageplaza' => 'mageplaza', 'core' => 'core'] as $source => $defaut) {
    foreach (Csv::lire("$sources/$source.csv") as $e) {
        $cle = $e['cle'];
        if (isset($communautaire[$cle]) && $communautaire[$cle] !== $cle) {
            $ecartees[] = $cle;
            continue;
        }
        // Identique a l'anglais : Magento affiche deja la cle, l'entree ne
        // sert a rien. Interdite ou portee par un module Maxcode : hors pack.
        if ($e['traduction'] === $cle || isset($interdites[$cle]) || isset($horsPack[$cle])) {
            $sansEffet[] = $cle;
            continue;
        }
        if (!isset($index[$cle]) && !isset($rangee[$cle])) {
            $inconnues[] = "$source\t$cle";
        }
        $editeur = $index[$cle] ?? $rangee[$cle] ?? $defaut;
        $rangee[$cle] = $editeur;
        $parFichier[$editeur][$cle] = Normaliseur::normaliser($e['traduction']);
    }
}
ksort($parFichier);
foreach ($parFichier as $editeur => $entrees) {
    Csv::ecrire("$racine/$editeur.csv", $entrees);
    printf("%-16s %5d entrée(s)\n", "$editeur.csv", count($entrees));
}
printf("Écartées (traduites par le communautaire) : %d\n", count($ecartees));
printf("Écartées (identiques à l'anglais, interdites, ou portées par un module Maxcode) : %d — %s\n",
    count($sansEffet), implode(' | ', $sansEffet));
file_put_contents(__DIR__ . '/a-traduire/reprise-ecartees.txt', implode("\n", array_merge($ecartees, $sansEffet)) . "\n");
file_put_contents(__DIR__ . '/a-traduire/reprise-inconnues.txt', implode("\n", $inconnues) . "\n");
printf("Clés absentes de l'index (rangées d'après leur source) : %d — outils/a-traduire/reprise-inconnues.txt\n", count($inconnues));
