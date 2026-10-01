<?php
declare(strict_types=1);

/**
 * Recolte les chaines traduisibles d'un Magento installe, par editeur.
 * Usage (racine Magento, via ddev exec) : php var/pack-fr/outils/extraire.php
 * Sources : collecteur de Magento (meme detecteur que i18n:collect-phrases) sur
 * chaque module, theme et lib/web, plus les i18n/en_US.csv.
 */
require __DIR__ . '/autoload.php';
require getcwd() . '/app/bootstrap.php';

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Setup\Module\I18n\ServiceLocator;
use Maxcode\LanguagePack\Outils\Classement;
use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Langue;

$langue = Langue::charger();
$sortie = dirname(__DIR__) . '/sortie';
@mkdir("$sortie/a-traduire", 0775, true);
$tmp = "$sortie/.phrases.csv";

// Le parseur de Magento CUMULE ses phrases d'un appel a l'autre
// (AbstractParser::$_phrases n'est jamais vide) et ServiceLocator garde le
// generateur en cache statique : on le jette avant chaque dossier, sinon
// chaque composant herite des phrases de tous les precedents.
$cacheGenerateur = new ReflectionProperty(ServiceLocator::class, '_dictionaryGenerator');

/** @return list<string> */
$phrases = static function (string $dossier) use ($tmp, $cacheGenerateur): array {
    if (!is_dir($dossier)) {
        return [];
    }
    @unlink($tmp);
    $cacheGenerateur->setValue(null, null);
    try {
        ServiceLocator::getDictionaryGenerator()->generate($dossier, $tmp, false);
    } catch (\UnexpectedValueException) {
        return []; // aucun texte traduisible dans ce dossier
    }
    $p = array_column(Csv::lire($tmp), 'cle');
    @unlink($tmp);

    return $p;
};

$registrar = new ComponentRegistrar();
$composants = [];
foreach ($registrar->getPaths(ComponentRegistrar::MODULE) as $nom => $chemin) {
    $composants[$nom] = ['chemin' => $chemin, 'front' => "$chemin/view/frontend"];
}
foreach ($registrar->getPaths(ComponentRegistrar::THEME) as $nom => $chemin) {
    $composants[$nom] = ['chemin' => $chemin, 'front' => str_starts_with($nom, 'frontend/') ? $chemin : null];
}
$composants['lib/web'] = ['chemin' => BP . '/lib/web', 'front' => null];

$exclus = array_values(array_filter(
    array_map('trim', file(__DIR__ . '/exclus.txt') ?: []),
    static fn (string $l): bool => $l !== '' && $l[0] !== '#'
));

$index = [];   // cle => [module, editeur, zone]
$jsEnDur = [];
foreach ($composants as $nom => $c) {
    if (Classement::exclu($nom, $exclus)) {
        continue;
    }
    $toutes = $phrases($c['chemin']);
    $enUs = $c['chemin'] . '/i18n/en_US.csv';
    if (is_file($enUs)) {
        $toutes = array_merge($toutes, array_column(Csv::lire($enUs), 'cle'));
    }
    $toutes = array_values(array_unique($toutes));
    if ($c['front'] === $c['chemin']) {
        $front = array_flip($toutes);
    } else {
        $front = $c['front'] !== null ? array_flip($phrases($c['front'])) : [];
    }

    // JavaScript qui COMPARE un texte affiche, hors $t( : une chaine qu'on y
    // trouve casserait l'ecran une fois traduite. Deux formes : la ligne qui
    // compare (== 'Texte'), et le fichier qui cherche un titre par
    // :contains( + variable, ou le texte est declare ailleurs dans le fichier
    // (cas « Manage Gallery »). Ce ne sont que des candidates, triees a la main.
    $lignesJs = [];
    if (is_dir($c['chemin'])) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($c['chemin'], FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.js') && !str_contains($f->getPathname(), '/node_modules/')) {
                $lignes = file($f->getPathname(), FILE_IGNORE_NEW_LINES) ?: [];
                $chercheUnTexte = str_contains(implode("\n", $lignes), ':contains(');
                foreach ($lignes as $ligne) {
                    if (($chercheUnTexte || preg_match('/[!=]=/', $ligne)) && !str_contains($ligne, '$t(')) {
                        $lignesJs[] = $ligne;
                    }
                }
            }
        }
    }
    $js = implode("\n", $lignesJs);

    foreach ($toutes as $cle) {
        $cle = (string) $cle;
        if ($cle === '' || isset($index[$cle])) {
            continue;
        }
        $index[$cle] = [$nom, Classement::editeur($nom), isset($front[$cle]) ? 'front' : 'admin'];
        if ($js !== '' && (str_contains($js, "'" . $cle . "'") || str_contains($js, '"' . $cle . '"'))) {
            $jsEnDur[$cle] = $nom;
        }
    }
}

// Deja traduites : paquets de langue de la locale du pack installes sur ce
// site (communautaire, et notre copie locale tant qu'elle existe).
$suffixe = '_' . strtolower($langue->locale);
$dejaTraduit = [];
foreach ($registrar->getPaths(ComponentRegistrar::LANGUAGE) as $nom => $chemin) {
    if (!str_ends_with(strtolower($nom), $suffixe)) {
        continue;
    }
    foreach (glob("$chemin/*.csv") ?: [] as $f) {
        foreach (Csv::lire($f) as $e) {
            if ($e['traduction'] !== $e['cle']) {
                $dejaTraduit[$e['cle']] = true;
            }
        }
    }
}

$h = fopen("$sortie/index.csv", 'wb');
$listes = [];
foreach ($index as $cle => [$module, $editeur, $zone]) {
    fputcsv($h, [(string) $cle, $module, $editeur, $zone], ',', '"', '', "\n");
    if (!isset($dejaTraduit[$cle])) {
        $listes[$editeur][] = [(string) $cle, '', $module, $zone];
    }
}
fclose($h);
foreach ($listes as $editeur => $lignes) {
    $h = fopen("$sortie/a-traduire/$editeur.csv", 'wb');
    foreach ($lignes as $l) {
        fputcsv($h, $l, ',', '"', '', "\n");
    }
    fclose($h);
}
file_put_contents("$sortie/js-en-dur.txt", implode("\n", array_map(
    static fn (string|int $c, string $m): string => "$m\t$c", array_keys($jsEnDur), $jsEnDur
)) . "\n");

printf("%d phrases, %d déjà traduites, %d comparées dans du JS (à trier), %d à traduire en %d fichier(s).\n",
    count($index), count(array_intersect_key($index, $dejaTraduit)), count($jsEnDur),
    array_sum(array_map('count', $listes)), count($listes));
