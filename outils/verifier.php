<?php
declare(strict_types=1);

/**
 * Controle tous les CSV a la racine du pack. Code de sortie 1 si au moins une
 * erreur. Usage : outils/php outils/verifier.php [--communautaire=<fichier>]
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Langue;
use Maxcode\LanguagePack\Outils\Verificateur;

$langue = Langue::charger();
if ($langue->typographie !== 'fr') {
    // Les regles de Typographie sont celles du francais : une autre langue
    // doit d'abord ecrire les siennes.
    fwrite(STDERR, "Pas de règles typographiques pour « {$langue->typographie} ».\n");
    exit(1);
}

$racine = dirname(__DIR__);
$options = getopt('', ['communautaire::']);
$cheminCommunautaire = $options['communautaire'] ?? __DIR__ . '/.cache/communautaire.csv';
if (!is_file($cheminCommunautaire)) {
    fwrite(STDERR, "Pack communautaire absent : lancer d'abord outils/php outils/telecharger-communautaire.php\n");
    exit(1);
}

$communautaire = [];
foreach (Csv::lire($cheminCommunautaire) as $e) {
    $communautaire[$e['cle']] = $e['traduction'];
}
$liste = static fn (string $f): array => array_values(array_filter(
    array_map('trim', file(__DIR__ . '/' . $f) ?: []),
    static fn (string $l): bool => $l !== '' && $l[0] !== '#'
));
$glossaire = [];
foreach (Csv::lire(__DIR__ . '/glossaire.csv') as $e) {
    $glossaire[$e['cle']] = $e['traduction'];
}

$fichiers = [];
foreach (glob($racine . '/*.csv') ?: [] as $chemin) {
    $fichiers[basename($chemin)] = $chemin;
}
ksort($fichiers);

$constats = (new Verificateur($communautaire, $liste('interdites.txt'), $liste('identiques-autorises.txt'), $glossaire))
    ->verifierPack($fichiers);

$erreurs = 0;
$avertissements = 0;
foreach ($constats as $c) {
    $c->niveau === 'erreur' ? $erreurs++ : $avertissements++;
    printf("%-13s %-16s %-20s l.%-5d %s\n", strtoupper($c->niveau), $c->fichier, $c->regle, $c->ligne, $c->message);
}
$entrees = array_sum(array_map(static fn (string $c): int => count(Csv::lire($c)), $fichiers));
printf("\n%d fichier(s), %d entrée(s) : %d erreur(s), %d avertissement(s).\n", count($fichiers), $entrees, $erreurs, $avertissements);
exit($erreurs === 0 ? 0 : 1);
