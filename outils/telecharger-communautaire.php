<?php
declare(strict_types=1);

/**
 * Telecharge le CSV du pack communautaire (paquet et fichier : langue.json),
 * a la version de outils/communautaire.version, depuis sa source publique
 * (sans identifiants Magento) : Packagist donne le commit, GitHub le fichier.
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Langue;

$langue = Langue::charger();
$version = trim((string) file_get_contents(__DIR__ . '/communautaire.version'));
$meta = json_decode((string) file_get_contents("https://repo.packagist.org/p2/{$langue->paquetCommunautaire}.json"), true);

// Format p2 « minifie » : chaque version ne porte que ce qui change par
// rapport a la precedente, il faut cumuler.
$entree = null;
$courante = [];
foreach ($meta['packages'][$langue->paquetCommunautaire] ?? [] as $v) {
    $courante = array_merge($courante, $v);
    if (ltrim((string) $courante['version'], 'v') === $version) {
        $entree = $courante;
        break;
    }
}
if ($entree === null) {
    fwrite(STDERR, "{$langue->paquetCommunautaire} $version introuvable sur Packagist.\n");
    exit(1);
}
$ref = $entree['source']['reference'];
$csv = file_get_contents("https://raw.githubusercontent.com/{$langue->depotCommunautaire}/$ref/{$langue->fichierCommunautaire}");
if ($csv === false || $csv === '') {
    fwrite(STDERR, "Téléchargement impossible (commit $ref).\n");
    exit(1);
}
@mkdir(__DIR__ . '/.cache', 0775, true);
file_put_contents(__DIR__ . '/.cache/communautaire.csv', $csv);
printf("%s %s (commit %s) : %d lignes, sha256 %s\n", $langue->paquetCommunautaire,
    $version, substr($ref, 0, 10), substr_count($csv, "\n"), substr(hash('sha256', $csv), 0, 16));
