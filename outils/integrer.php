<?php
declare(strict_types=1);

/**
 * Integre outils/a-traduire/<editeur>.csv (lignes traduites) dans <editeur>.csv
 * et ajoute ses cles « front » a outils/zones/<editeur>.txt.
 * Usage : outils/php outils/integrer.php <editeur>
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Integration;

$editeur = $argv[1] ?? '';
$racine = dirname(__DIR__);
$liste = __DIR__ . "/a-traduire/$editeur.csv";
if ($editeur === '' || !is_file($liste)) {
    fwrite(STDERR, "Usage : outils/php outils/integrer.php <editeur>  (liste attendue : $liste)\n");
    exit(1);
}

$cible = "$racine/$editeur.csv";
$existant = [];
if (is_file($cible)) {
    foreach (Csv::lire($cible) as $e) {
        $existant[$e['cle']] = $e['traduction'];
    }
}
$autres = [];
foreach (glob("$racine/*.csv") ?: [] as $f) {
    if ($f !== $cible) {
        foreach (Csv::lire($f) as $e) {
            $autres[$e['cle']] = basename($f);
        }
    }
}
$communautaire = [];
foreach (Csv::lire(__DIR__ . '/.cache/communautaire.csv') as $e) {
    $communautaire[$e['cle']] = $e['traduction'];
}

// La liste de travail a 4 colonnes ; Csv::lire n'en rend que 2.
$lignes = [];
$h = fopen($liste, 'rb');
while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
    if ($l !== [null] && count($l) >= 4) {
        $lignes[] = ['cle' => $l[0], 'traduction' => $l[1], 'module' => $l[2], 'zone' => $l[3]];
    }
}
fclose($h);

$r = Integration::integrer($existant, $lignes, $autres, $communautaire);
Csv::ecrire($cible, $r['entrees']);

$zones = __DIR__ . "/zones/$editeur.txt";
@mkdir(__DIR__ . '/zones', 0775, true);
$front = is_file($zones) ? (file($zones, FILE_IGNORE_NEW_LINES) ?: []) : [];
$front = array_values(array_unique(array_merge($front, $r['front'])));
sort($front, SORT_STRING);
file_put_contents($zones, $front === [] ? '' : implode("\n", $front) . "\n");

printf("%s.csv : %d entrée(s) (+%d), %d refusée(s).\n", $editeur, count($r['entrees']),
    count($r['entrees']) - count($existant), count($r['refusees']));
foreach ($r['refusees'] as $x) {
    printf("  refusée : %s — %s\n", mb_substr($x['cle'], 0, 70), $x['raison']);
}
