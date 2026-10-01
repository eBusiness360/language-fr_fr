<?php
declare(strict_types=1);

/**
 * Fusionne les extractions des sites (outils/a-traduire/sites/<nom>/) :
 * une liste de travail par editeur (union ; « front » si front sur un site),
 * un index global, et la liste des chaines comparees dans du JS.
 * Les lignes deja traduites dans une liste existante sont conservees ; les
 * cles deja presentes dans un CSV du pack sont ecartees (a relancer apres la
 * reprise de l'existant).
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Verificateur;

$base = __DIR__ . '/a-traduire';
$dansLePack = []; // cle => fichier du pack
foreach (glob(dirname(__DIR__) . '/*.csv') ?: [] as $f) {
    foreach (Csv::lire($f) as $e) {
        $dansLePack[$e['cle']] = basename($f);
    }
}
$utiles = [];     // cle => true : a traduire sur au moins un site
$listes = [];
$index = [];
$js = [];
foreach (glob("$base/sites/*", GLOB_ONLYDIR) ?: [] as $site) {
    foreach (glob("$site/a-traduire/*.csv") ?: [] as $f) {
        $editeur = basename($f, '.csv');
        $h = fopen($f, 'rb');
        while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($l === [null] || count($l) < 4) {
                continue;
            }
            $utiles[$l[0]] = true;
            if (isset($dansLePack[$l[0]])) {
                continue;
            }
            $actuelle = $listes[$editeur][$l[0]] ?? null;
            $zone = ($actuelle['zone'] ?? 'admin') === 'front' || $l[3] === 'front' ? 'front' : 'admin';
            $listes[$editeur][$l[0]] = ['module' => $l[2], 'zone' => $zone];
        }
        fclose($h);
    }
    $h = fopen("$site/index.csv", 'rb');
    while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
        if ($l !== [null] && count($l) >= 4) {
            $index[$l[0]] ??= $l;
        }
    }
    fclose($h);
    foreach (file("$site/js-en-dur.txt", FILE_IGNORE_NEW_LINES) ?: [] as $ligne) {
        if ($ligne !== '') {
            $js[$ligne] = true;
        }
    }
}

ksort($listes);
foreach ($listes as $editeur => $lignes) {
    $cible = "$base/$editeur.csv";
    $traduites = [];
    if (is_file($cible)) {
        $h = fopen($cible, 'rb');
        while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($l !== [null] && ($l[1] ?? '') !== '') {
                $traduites[$l[0]] = $l[1];
            }
        }
        fclose($h);
    }
    ksort($lignes, SORT_STRING);
    $h = fopen($cible, 'wb');
    foreach ($lignes as $cle => $info) {
        fputcsv($h, [(string) $cle, $traduites[$cle] ?? '', $info['module'], $info['zone']], ',', '"', '', "\n");
    }
    fclose($h);
    printf("%-14s %5d à traduire\n", $editeur, count($lignes));
}
$h = fopen("$base/index.csv", 'wb');
foreach ($index as $l) {
    fputcsv($h, $l, ',', '"', '', "\n");
}
fclose($h);
ksort($js);
file_put_contents("$base/js-en-dur.txt", implode("\n", array_keys($js)) . "\n");

// Cles du pack devenues inutiles : vues sur un site (dans l'index) mais a
// traduire nulle part, parce que le communautaire ou le module qui les
// utilise les traduit. Les cles hors index (reprises sans source connue) et
// les corrections du communautaire restent. outils/elaguer.php les retire.
$h = fopen("$base/inutiles.csv", 'wb');
$inutiles = 0;
foreach ($dansLePack as $cle => $fichier) {
    if ($fichier !== Verificateur::CORRECTIONS && isset($index[$cle]) && !isset($utiles[$cle])) {
        fputcsv($h, [(string) $cle, $fichier], ',', '"', '', "\n");
        $inutiles++;
    }
}
fclose($h);
printf("Index : %d phrases ; %d chaînes comparées dans du JS, à trier (outils/a-traduire/js-en-dur.txt).\n", count($index), count($js));
printf("%d clé(s) du pack devenue(s) inutile(s) (outils/a-traduire/inutiles.csv, à retirer avec outils/elaguer.php).\n", $inutiles);
