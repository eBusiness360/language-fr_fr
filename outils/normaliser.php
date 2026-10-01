<?php
declare(strict_types=1);

/** Normalise la typographie d'un CSV du pack, en place. */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Normaliseur;

$fichier = $argv[1] ?? '';
if (!is_file($fichier)) {
    fwrite(STDERR, "Usage : outils/php outils/normaliser.php <fichier.csv>\n");
    exit(1);
}
$entrees = [];
$modifiees = 0;
foreach (Csv::lire($fichier) as $e) {
    $n = Normaliseur::normaliser($e['traduction']);
    $modifiees += (int) ($n !== $e['traduction']);
    $entrees[$e['cle']] = $n;
}
Csv::ecrire($fichier, $entrees);
printf("%s : %d entrée(s), %d corrigée(s).\n", basename($fichier), count($entrees), $modifiees);
