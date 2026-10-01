<?php
declare(strict_types=1);

/**
 * Traduire une liste de travail par lots, sans jamais recopier une cle :
 *
 *   outils/php outils/lot.php montrer   <editeur> <debut> <nombre>
 *       affiche les lignes NON traduites, numerotees (numero = ligne de la liste),
 *       sauts de ligne de la cle rendus « \n » ;
 *   outils/php outils/lot.php appliquer <editeur> <fichier>
 *       lit des lignes « numero<TAB>traduction » (« \n » = saut de ligne) et
 *       remplit la colonne traduction de ces lignes de la liste.
 *   outils/php outils/lot.php etat      <editeur>
 *       nombre de lignes traduites / restantes.
 *
 * La liste (outils/a-traduire/<editeur>.csv) garde ses 4 colonnes :
 * cle, traduction, module, zone. Une ligne laissee vide n'entre pas dans le pack.
 */
$action = $argv[1] ?? '';
$editeur = $argv[2] ?? '';
$liste = __DIR__ . "/a-traduire/$editeur.csv";
if (!in_array($action, ['montrer', 'appliquer', 'etat'], true) || !is_file($liste)) {
    fwrite(STDERR, "Usage : lot.php montrer|appliquer|etat <editeur> ...\n");
    exit(1);
}

/** @return list<array{0: string, 1: string, 2: string, 3: string}> */
$lire = static function (string $f): array {
    $l = [];
    $h = fopen($f, 'rb');
    while (($r = fgetcsv($h, 0, ',', '"', '')) !== false) {
        if ($r !== [null] && count($r) >= 4) {
            $l[] = [(string) $r[0], (string) $r[1], (string) $r[2], (string) $r[3]];
        }
    }
    fclose($h);

    return $l;
};
$lignes = $lire($liste);
$echapper = static fn (string $s): string => str_replace(["\r", "\n"], ['', '\n'], $s);

if ($action === 'etat') {
    $faites = count(array_filter($lignes, static fn (array $l): bool => $l[1] !== ''));
    printf("%s : %d traduites, %d restantes sur %d\n", $editeur, $faites, count($lignes) - $faites, count($lignes));
    exit(0);
}

if ($action === 'montrer') {
    $debut = (int) ($argv[3] ?? 1);
    $nombre = (int) ($argv[4] ?? 200);
    $montrees = 0;
    foreach ($lignes as $i => $l) {
        $n = $i + 1;
        if ($n < $debut || $l[1] !== '') {
            continue;
        }
        printf("%d\t%s\t%s\t%s\n", $n, $l[2], $l[3], $echapper($l[0]));
        if (++$montrees >= $nombre) {
            break;
        }
    }
    exit(0);
}

// appliquer
$fichier = $argv[3] ?? '';
$appliquees = 0;
foreach (file($fichier, FILE_IGNORE_NEW_LINES) ?: [] as $ligne) {
    if (!preg_match('/^(\d+)\t(.*)$/u', $ligne, $m)) {
        continue;
    }
    $i = (int) $m[1] - 1;
    if (!isset($lignes[$i])) {
        fwrite(STDERR, "Ligne {$m[1]} inexistante dans $editeur.csv\n");
        exit(1);
    }
    $lignes[$i][1] = str_replace('\n', "\n", $m[2]);
    $appliquees++;
}
$h = fopen($liste, 'wb');
foreach ($lignes as $l) {
    fputcsv($h, $l, ',', '"', '', "\n");
}
fclose($h);
printf("%s : %d traduction(s) appliquée(s).\n", $editeur, $appliquees);
