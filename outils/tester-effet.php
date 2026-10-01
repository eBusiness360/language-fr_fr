<?php
declare(strict_types=1);

/**
 * Prouve, dans un Magento installe, que le pack est lu et qu'il l'emporte.
 * Usage (racine Magento, pack installe par Composer) :
 *     php var/pack-fr/outils/tester-effet.php
 * Controles :
 *   1. le composant du pack (langue.json) est enregistre, depuis vendor/
 *      (pas une copie locale) ;
 *   2. pour CHAQUE fichier du pack, une chaine temoin ressort avec notre
 *      traduction en frontend et en adminhtml ;
 *   3. aucune cle du pack n'est traduite par le pack communautaire INSTALLE
 *      sur ce site (sinon nous l'ecraserions).
 */
require __DIR__ . '/autoload.php';
require getcwd() . '/app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\Component\ComponentRegistrar;
use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Langue;

$langue = Langue::charger();
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$langues = (new ComponentRegistrar())->getPaths(ComponentRegistrar::LANGUAGE);
$echecs = 0;

$pack = $langues[$langue->composant] ?? null;
printf("%s : %s\n", $langue->composant, $pack ?? 'NON ENREGISTRÉ');
if ($pack === null || !str_contains($pack, '/vendor/' . $langue->paquet)) {
    echo "ÉCHEC : le pack doit venir de vendor/{$langue->paquet} (copie locale restante ?)\n";
    exit(1);
}

[$vendeurCommunautaire] = explode('/', $langue->paquetCommunautaire);
$communautaire = [];
foreach ($langues as $nom => $chemin) {
    if ($nom !== $langue->composant && str_contains($chemin, '/vendor/' . $vendeurCommunautaire . '/')) {
        foreach (glob("$chemin/*.csv") ?: [] as $f) {
            foreach (Csv::lire($f) as $e) {
                $communautaire[$e['cle']] = $e['traduction'];
            }
        }
    }
}
printf("pack communautaire installé : %d entrées\n", count($communautaire));

$temoins = [];
foreach (glob("$pack/*.csv") ?: [] as $f) {
    $entrees = Csv::lire($f);
    if ($entrees !== []) {
        $temoins[basename($f)] = $entrees[0];
    }
    foreach ($entrees as $e) {
        if (isset($communautaire[$e['cle']]) && $communautaire[$e['cle']] !== $e['cle']) {
            printf("ÉCHEC : %s écrase le communautaire installé — « %s »\n", basename($f), mb_substr($e['cle'], 0, 60));
            $echecs++;
        }
    }
}

$etat = $om->get(\Magento\Framework\App\State::class);
foreach (['frontend', 'adminhtml'] as $aire) {
    // Les traductions de theme exigent une aire : on l'emule le temps du chargement.
    $donnees = $etat->emulateAreaCode($aire, static function () use ($om, $langue, $aire): array {
        $t = $om->create(\Magento\Framework\Translate::class);
        $t->setLocale($langue->locale)->loadData($aire, true);

        return $t->getData();
    });
    foreach ($temoins as $fichier => $e) {
        $obtenu = $donnees[$e['cle']] ?? '(absente)';
        $ok = $obtenu === $e['traduction'];
        printf("%-10s %-16s %-6s « %s » → « %s »\n", $aire, $fichier, $ok ? 'OK' : 'ÉCHEC',
            mb_substr($e['cle'], 0, 40), mb_substr($obtenu, 0, 40));
        $echecs += (int) !$ok;
    }
}

echo $echecs === 0 ? "\nLe pack est chargé et l'emporte.\n" : "\nÉCHEC : $echecs contrôle(s).\n";
exit($echecs === 0 ? 0 : 1);
