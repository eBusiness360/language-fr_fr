<?php
declare(strict_types=1);

/**
 * Retire du pack les cles listees par outils/fusionner.php dans
 * outils/a-traduire/inutiles.csv (traduites ailleurs : communautaire, ou le
 * module qui les utilise). Usage : outils/php outils/elaguer.php
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;

$liste = __DIR__ . '/a-traduire/inutiles.csv';
if (!is_file($liste)) {
    fwrite(STDERR, "Liste absente : lancer d'abord outils/php outils/fusionner.php\n");
    exit(1);
}
$parFichier = [];
foreach (Csv::lire($liste) as $e) {
    $parFichier[$e['traduction']][$e['cle']] = true; // 2e colonne : fichier du pack
}
$racine = dirname(__DIR__);
foreach ($parFichier as $fichier => $cles) {
    $chemin = "$racine/" . basename((string) $fichier);
    if (!is_file($chemin)) {
        continue;
    }
    $gardees = [];
    foreach (Csv::lire($chemin) as $e) {
        if (!isset($cles[$e['cle']])) {
            $gardees[$e['cle']] = $e['traduction'];
        }
    }
    $retirees = count(Csv::lire($chemin)) - count($gardees);
    if ($gardees === []) {
        unlink($chemin);
        printf("%-18s supprimé (toutes ses clés sont traduites ailleurs)\n", basename($chemin));
        continue;
    }
    Csv::ecrire($chemin, $gardees);
    printf("%-18s %5d clé(s) retirée(s), %5d gardée(s)\n", basename($chemin), $retirees, count($gardees));
}
