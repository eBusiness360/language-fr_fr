<?php
declare(strict_types=1);

/**
 * Fiche de relecture d'un editeur : toutes les chaines vues en front, puis 30
 * chaines d'administration tirees au hasard (graine fixe par jour).
 * Usage : outils/php outils/echantillon.php <editeur> [nombre-admin]
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;

$editeur = $argv[1] ?? '';
$nombre = (int) ($argv[2] ?? 30);
$fichier = dirname(__DIR__) . "/$editeur.csv";
if ($editeur === '' || !is_file($fichier)) {
    fwrite(STDERR, "Usage : outils/php outils/echantillon.php <editeur> [nombre]\n");
    exit(1);
}
$zones = __DIR__ . "/zones/$editeur.txt";
$front = is_file($zones) ? array_flip(file($zones, FILE_IGNORE_NEW_LINES) ?: []) : [];

$enFront = [];
$admin = [];
foreach (Csv::lire($fichier) as $e) {
    if (isset($front[$e['cle']])) {
        $enFront[] = $e;
    } else {
        $admin[] = $e;
    }
}
$totalAdmin = count($admin);
mt_srand(crc32($editeur . date('Ymd')));
shuffle($admin);
$admin = array_slice($admin, 0, $nombre);

$cellule = static fn (string $s): string => str_replace(['|', "\n"], ['\|', ' '], $s);
$md = "# Relecture — $editeur (" . date('d/m/Y') . ")\n\n";
$md .= sprintf("Parcours client : %d chaîne(s), toutes. Administration : %d sur %d.\n", count($enFront), count($admin), $totalAdmin);
$md .= "Annoter la colonne « Remarque » ; laisser vide si c'est bon.\n\n";
foreach (['Parcours client' => $enFront, 'Administration (au hasard)' => $admin] as $titre => $lignes) {
    $md .= "## $titre\n\n| # | Anglais | Français | Remarque |\n|---|---|---|---|\n";
    foreach ($lignes as $i => $e) {
        $md .= sprintf("| %d | %s | %s | |\n", $i + 1, $cellule($e['cle']), $cellule($e['traduction']));
    }
    $md .= "\n";
}
@mkdir(__DIR__ . '/echantillons', 0775, true);
$sortie = __DIR__ . "/echantillons/$editeur-" . date('Ymd') . '.md';
file_put_contents($sortie, $md);
echo "Fiche : $sortie\n";
