# Pack de traduction français gratuit — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** publier `maxcode/language-fr_fr` 2.0.0 (pack de langue gratuit, complément du pack communautaire) et `maxcode/module-translation-fixes` 1.0.0 (correctifs de code), puis migrer ttlx, Ambiance et mojo.

**Architecture :** un pack de langue Magento par-dessus `community-engineering/language-fr_fr`, un CSV par éditeur à la racine, outillé par des scripts PHP sans dépendance (extraction, contrôles bloquants, normalisation, intégration, test d'effet, échantillons) et une CI GitHub Actions. Un module compagnon sans texte français rend traduisibles les chaînes qui échappent à `__()` / `$t()`.

**Tech Stack :** PHP 8.3 (CLI, via Docker `php:8.3-cli` ou DDEV — WSL n'a pas de PHP), PHPUnit 10 (phar), Magento 2.4.8-p5 (DDEV : ttlx-local, ambiance-m2, mojo-m2), Composer, GitHub Actions.

**Spec :** `docs/superpowers/specs/2026-10-01-pack-traduction-fr-design.md` (déplacée dans le dépôt du pack à la tâche 1).

## Global Constraints

- Pack : nom Composer `maxcode/language-fr_fr`, type `magento2-language`, `language.xml` : `vendor maxcode`, `package fr_fr`, `sort_order 100`, `use vendor="community-engineering" package="fr_fr"`.
- Compagnon : `maxcode/module-translation-fixes`, module `Maxcode_TranslationFixes`, **aucun texte français**.
- **Sites : le pack communautaire passe à `^0.0.64`** (les trois sites exigeaient `^0.0.61`, qui fige 0.0.61 et entre en conflit avec le pack). Toute commande `composer require` du pack sur un site inclut `'community-engineering/language-fr_fr:^0.0.64'` (constaté sur ttlx le 01/10/2026).
- **Mémoire WSL (7,8 Go) :** une extraction à la fois, un seul DDEV démarré pendant l'extraction (`ddev start` → `outils/extraction.sh` → `ddev stop`) ; trois Magento + une extraction à 4 Go ont figé WSL le 01/10/2026.
- **Identité git des dépôts publics :** `910330+eBusiness360@users.noreply.github.com` (l'adresse réelle est privée sur GitHub, protection GH007).
- **Multilingue (demande de l'utilisateur, 01/10/2026) :** le français ne vit que dans les CSV du pack et dans ses données linguistiques (`outils/glossaire.csv`, règles de `Typographie`/`Normaliseur`, marquées « règles du français »). Le compagnon et tout module Maxcode n'écrivent que des chaînes sources **anglaises** passées par `__()` / `$t()` — jamais de texte dans une langue cible codé en dur (l'anti-modèle : le `di.xml` de CoreTranslation qui écrivait « Connexion en tant que client »). Les outils ne codent jamais `fr_FR`, `maxcode_fr_fr` ni le paquet communautaire : ils lisent `outils/langue.json` via `Langue::charger()` (champs `locale`, `composant`, `paquet`, `communautaire.{paquet,depot,fichier}`, `typographie`). Les blocs de code des tâches 4, 7 et 8 qui écrivent ces valeurs en dur sont à implémenter avec `Langue` à la place. Un futur pack `language-de_de` = copie de `outils/` + nouveau `langue.json` + glossaire et typographie de la langue.
- Licence **MIT**, `Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER`, sur les deux dépôts.
- Pack : `require` `community-engineering/language-fr_fr` `>=0.0.64` et `magento/framework` `^103.0` ; `suggest` le compagnon. Pas de champ `version` (les tags font foi).
- Dépôts `github.com/eBusiness360/language-fr_fr` et `github.com/eBusiness360/module-translation-fixes`, **publics dès leur création** (choix de l’utilisateur, 01/10/2026) ; ils ne sont enregistrés sur Packagist qu’au tag 2.0.0 / 1.0.0.
- Une chaîne n'entre que si le pack communautaire **ne la traduit pas** (clé absente, ou traduction identique à l'anglais). Jamais de traduction reprise d'un `fr_FR.csv` d'éditeur. Jamais les chaînes des modules `Maxcode_*`.
- **Une clé dans un seul fichier** du pack.
- CSV : 2 colonnes, guillemets doublés, UTF-8 sans BOM, fins de ligne `\n`, tri par clé (`strcmp`), chaque champ entre guillemets.
- Fichiers d'éditeur nommés d'après le **préfixe du module en minuscules** (`Amasty_*` → `amasty.csv`, `Bss_*` → `bss.csv`, `MageWorx_*` → `mageworx.csv`) ; cœur `Magento_*` → `core.csv`.
- Typographie bloquante : apostrophe `’` (U+2019) ; insécable U+00A0 avant `: ; ! ?` et dans `« »` ; `« »` pour le texte, `"` seulement dans le balisage ; `…` (U+2026) ; `%1 %s %d {{…}}` et balises HTML conservés en même nombre.
- Avertissements (non bloquants) : glossaire, majuscules de titre, tutoiement.
- **Ne jamais écrire `>>` dans le texte d'une commande passée à l'outil Bash** (un hook local injecte ses lignes dans le fichier cible) ; écrire les fichiers avec Write/Edit, ou mettre la redirection dans un script.
- Commandes WSL depuis l'outil Bash : préfixer `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "…"`. `wsl.exe` abîme parfois `$` et les accents graves : si une commande en contient (boucles `for e in …; do … \$e …`, `\$?`) et échoue bizarrement, l'écrire dans un script du scratchpad et lancer `wsl.exe -d Ubuntu -e bash <chemin /mnt/c/… du script>`.
- Fin de chaque message de commit : `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Répertoires

| Chemin (WSL) | Rôle |
|---|---|
| `/home/maxime/projects/language-fr_fr` | copie de travail du dépôt du pack |
| `/home/maxime/projects/module-translation-fixes` | copie de travail du dépôt du compagnon |
| `/home/maxime/projects/ttlx-local` | site de développement (DDEV) ; branche `feature/pack-traduction-fr` |
| `/home/maxime/projects/ambiance-m2`, `/home/maxime/projects/mojo-m2` | sites dont on extrait aussi les chaînes |

## Structure de fichiers

```
language-fr_fr/
├── core.csv, amasty.csv, … (créés par l'intégration, tâche 12 et suivantes)
├── language.xml, registration.php, composer.json, LICENSE, README.md, CHANGELOG.md
├── .gitattributes, .gitignore
├── docs/superpowers/{specs,plans}/
├── .github/workflows/verifier.yml
└── outils/
    ├── php, phpunit                    enveloppes Docker (bash)
    ├── phpunit.xml
    ├── communautaire.version           version du pack communautaire de référence
    ├── glossaire.csv, interdites.txt, identiques-autorises.txt
    ├── zones/<editeur>.txt             clés vues en front (versionné)
    ├── src/  Csv.php  Typographie.php  Variables.php  Normaliseur.php
    │         Constat.php  Verificateur.php  Classement.php  Integration.php
    ├── tests/  CsvTest.php  TypographieTest.php  VariablesTest.php  NormaliseurTest.php
    │           VerificateurTest.php  ClassementTest.php  IntegrationTest.php
    ├── telecharger-communautaire.php  verifier.php  normaliser.php  integrer.php
    ├── extraire.php  extraction.sh  fusionner.php  reprendre-existant.php
    ├── tester-effet.php  echantillon.php
    └── a-traduire/, echantillons/, .cache/   (générés, ignorés par git)

module-translation-fixes/
├── composer.json, registration.php, LICENSE, README.md, CHANGELOG.md
├── etc/module.xml, etc/adminhtml/di.xml
├── Plugin/LoginAsCustomer/TraduireLibelleBouton.php
├── Test/Unit/Plugin/LoginAsCustomer/TraduireLibelleBoutonTest.php
└── view/adminhtml/requirejs-config.js
    view/adminhtml/web/js/media-gallery/massaction-view-mixin.js
```

---

## Phase 0 — Socle

### Task 1 : Dépôts et squelette du pack

**Files :**
- Create : `language-fr_fr/{composer.json, registration.php, language.xml, LICENSE, README.md, CHANGELOG.md, .gitattributes, .gitignore}`
- Move : spec et plan depuis `ttlx-local/docs/superpowers/` vers `language-fr_fr/docs/superpowers/`

**Interfaces :** Produces : le dépôt `language-fr_fr` poussé sur `eBusiness360/language-fr_fr` (branche `main`).

- [ ] **Step 1 : l'utilisateur crée les deux dépôts PUBLICS et vides (fait le 01/10/2026)** sur GitHub (`eBusiness360/language-fr_fr`, `eBusiness360/module-translation-fixes`), sans README ni licence. Vérifier :

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "git ls-remote git@github.com:eBusiness360/language-fr_fr.git; git ls-remote git@github.com:eBusiness360/module-translation-fixes.git; echo fin"`
Expected : aucune erreur, aucune référence, puis `fin`.

- [ ] **Step 2 : écrire `composer.json`**

```json
{
    "name": "maxcode/language-fr_fr",
    "description": "Complément français pour Magento 2 : ce que le pack communautaire ne traduit pas — cœur et modules tiers courants.",
    "type": "magento2-language",
    "license": "MIT",
    "authors": [
        {"name": "eBusiness360 – Maxime LESGUILLIER", "homepage": "https://github.com/eBusiness360"}
    ],
    "require": {
        "magento/framework": "^103.0",
        "community-engineering/language-fr_fr": ">=0.0.64"
    },
    "suggest": {
        "maxcode/module-translation-fixes": "Rend traduisibles les chaînes que Magento n’expose pas à la traduction"
    },
    "autoload": {
        "files": ["registration.php"]
    }
}
```

- [ ] **Step 3 : écrire `registration.php`**

```php
<?php
/**
 * maxcode/language-fr_fr — MIT, Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::LANGUAGE, 'maxcode_fr_fr', __DIR__);
```

- [ ] **Step 4 : écrire `language.xml`** — reprend le fichier actuel d'Ambiance (`ambiance-m2/app/i18n/maxcode/fr_fr/language.xml`) en remplaçant l'en-tête de licence par la mention MIT. Contenu complet :

```xml
<?xml version="1.0"?>
<!--
/**
 * maxcode/language-fr_fr — licence MIT.
 * Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 */
-->
<language xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="urn:magento:framework:App/Language/package.xsd">
    <code>fr_FR</code>
    <vendor>maxcode</vendor>
    <package>fr_fr</package>

    <!--
        PRIORITE : ce paquet doit gagner sur celui de la communaute.

        Magento charge les traductions dans cet ordre (Framework\Translate::loadData) :
            module  ->  PAQUET DE LANGUE  ->  theme  ->  base
        Le dernier charge l'emporte. Entre paquets, l'arbitrage se fait sur
        sort_order (App\Language\Dictionary). community-engineering/language-fr_fr
        n'en declare AUCUN, donc 0. 100 garantit la priorite.

        Tous les *.csv de la RACINE de ce paquet sont lus, par ordre alphabetique
        (Dictionary::readPackCsv). Une meme cle dans deux fichiers serait arbitree
        en silence par l'alphabet : d'ou la regle « une cle, un fichier ».
    -->
    <sort_order>100</sort_order>

    <use vendor="community-engineering" package="fr_fr"/>

    <!--
        REGLES D'ECRITURE — controlees par outils/verifier.php.

        1. Apostrophe typographique U+2019, jamais l'apostrophe droite : Magento
           insere certaines traductions dans une chaine JavaScript entre
           apostrophes simples, dans un attribut data-bind. Une apostrophe droite
           ferme la chaine, Knockout tombe (« Unable to parse bindings »).
        2. Chaines comparees en dur dans du JavaScript : interdites tant que le
           module maxcode/module-translation-fixes n'a pas corrige le code
           (outils/interdites.txt).
    -->
</language>
```

- [ ] **Step 5 : écrire `LICENSE`** (texte MIT complet)

```
MIT License

Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

- [ ] **Step 6 : écrire `README.md`**

```markdown
# Complément français pour Magento 2

Traduit ce que le pack communautaire [`community-engineering/language-fr_fr`](https://github.com/magento-l10n/language-fr_FR)
ne traduit pas : le reste du cœur Magento et les modules tiers courants (Amasty, Mageplaza,
Mirasvit, MageWorx, Magefan, Smile, Fooman, BSS, Xtento, Magezon, Porto, Swissup Breeze…).
Il s'installe **par-dessus** le pack communautaire et ne modifie aucune de ses traductions.

## Installation

    composer require maxcode/language-fr_fr
    bin/magento setup:upgrade
    bin/magento cache:clean translate
    bin/magento setup:static-content:deploy fr_FR   # en mode production

Facultatif — pour les chaînes que Magento n'expose pas à la traduction :

    composer require maxcode/module-translation-fixes

Les traductions de modules absents de votre boutique sont sans effet.

## Contribuer

Une clé n'entre dans le pack que si le pack communautaire ne la traduit pas. Un fichier par
éditeur (`amasty.csv`, `mageworx.csv`…), `core.csv` pour le cœur. Avant toute proposition :

    outils/php outils/telecharger-communautaire.php
    outils/php outils/verifier.php

Les règles (apostrophe `’`, espace insécable avant `: ; ! ?`, `« »`, `…`, variables conservées)
sont contrôlées par la CI.

## Licence

MIT — © 2026 eBusiness360 – Maxime LESGUILLIER.
```

- [ ] **Step 7 : écrire `CHANGELOG.md`**

```markdown
# Changelog — maxcode/language-fr_fr

Format : [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions : [SemVer](https://semver.org/lang/fr/).

## [Non publié]

### Ajouté
- Organisation en un fichier CSV par éditeur, outillage de contrôle et CI.
```

- [ ] **Step 8 : écrire `.gitattributes` et `.gitignore`**

`.gitattributes` :
```
/outils      export-ignore
/.github     export-ignore
/docs        export-ignore
/.gitattributes export-ignore
/.gitignore  export-ignore
```

`.gitignore` :
```
/outils/a-traduire/
/outils/echantillons/
/outils/.cache/
```

- [ ] **Step 9 : déplacer la spec et le plan, valider `composer.json`, premier commit, push**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && mkdir -p docs/superpowers/specs docs/superpowers/plans && mv ../ttlx-local/docs/superpowers/specs/2026-10-01-pack-traduction-fr-design.md docs/superpowers/specs/ && mv ../ttlx-local/docs/superpowers/plans/2026-10-01-pack-traduction-fr.md docs/superpowers/plans/ && docker run --rm -v /home/maxime/projects/language-fr_fr:/app -w /app composer:2 validate --no-check-publish --no-check-lock && git init -q -b main && git config user.name 'Maxime LESGUILLIER' && git config user.email 'maxime@ebusiness-atlantique.fr' && git add -A && git commit -q -m 'chore: squelette du pack maxcode/language-fr_fr (MIT)' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git remote add origin git@github.com:eBusiness360/language-fr_fr.git && git push -q -u origin main && git log --oneline -1"`
Expected : `./composer.json is valid`, puis le hash du commit.
---

### Task 2 : Enveloppes Docker et lecture/écriture CSV

**Files :**
- Create : `outils/php`, `outils/phpunit`, `outils/phpunit.xml`, `outils/autoload.php`, `outils/src/Csv.php`
- Test : `outils/tests/CsvTest.php`

**Interfaces :**
- Produces : `Maxcode\LanguagePack\Outils\Csv::lire(string $fichier): list<array{cle:string, traduction:string, ligne:int, champs:int}>` ; `Csv::ecrire(string $fichier, array<string,string> $entrees): void` (tri `strcmp`, chaque champ entre guillemets, `\n`). Enveloppes `outils/php <script> [args]` et `outils/phpunit`.

- [ ] **Step 1 : écrire `outils/php`** (exécutable, `chmod +x`)

```bash
#!/usr/bin/env bash
# Lance PHP 8.3 dans un conteneur sur ce depot : WSL n'a pas de PHP.
racine="$(cd "$(dirname "$0")/.." && pwd)"
exec docker run --rm -i -u "$(id -u):$(id -g)" -v "$racine":/app -w /app php:8.3-cli php "$@"
```

- [ ] **Step 2 : écrire `outils/phpunit`** (exécutable)

```bash
#!/usr/bin/env bash
# PHPUnit 10 en phar, telecharge une fois dans outils/.cache.
set -euo pipefail
racine="$(cd "$(dirname "$0")/.." && pwd)"
phar="$racine/outils/.cache/phpunit.phar"
if [ ! -f "$phar" ]; then
    mkdir -p "$racine/outils/.cache"
    curl -sSL https://phar.phpunit.de/phpunit-10.phar -o "$phar"
fi
exec "$racine/outils/php" outils/.cache/phpunit.phar -c outils/phpunit.xml "$@"
```

- [ ] **Step 3 : écrire `outils/phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="autoload.php" colors="false" failOnWarning="true">
    <testsuites>
        <testsuite name="outils">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Et `outils/autoload.php` (partagé par les tests et toutes les commandes) :

```php
<?php
declare(strict_types=1);

spl_autoload_register(static function (string $classe): void {
    $prefixe = 'Maxcode\\LanguagePack\\Outils\\';
    if (str_starts_with($classe, $prefixe)) {
        require __DIR__ . '/src/' . substr($classe, strlen($prefixe)) . '.php';
    }
});
```

- [ ] **Step 4 : écrire le test qui échoue** `outils/tests/CsvTest.php`

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Csv;
use PHPUnit\Framework\TestCase;

final class CsvTest extends TestCase
{
    private string $fichier;

    protected function setUp(): void
    {
        $this->fichier = sys_get_temp_dir() . '/csv-' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->fichier);
    }

    public function testEcrireTrieQuoteToutEtRelitALIdentique(): void
    {
        Csv::ecrire($this->fichier, [
            'Zebra' => 'Zèbre',
            'Say "hi"' => 'Dites « bonjour »',
            '404' => 'Introuvable',
            'Active filters\\:' => 'Filtres actifs :',
        ]);

        $brut = (string) file_get_contents($this->fichier);
        self::assertSame(
            "\"404\",\"Introuvable\"\n"
            . "\"Active filters\\:\",\"Filtres actifs :\"\n"
            . "\"Say \"\"hi\"\"\",\"Dites « bonjour »\"\n"
            . "\"Zebra\",\"Zèbre\"\n",
            $brut
        );

        $relu = Csv::lire($this->fichier);
        self::assertCount(4, $relu);
        self::assertSame('Say "hi"', $relu[2]['cle']);
        self::assertSame(3, $relu[2]['ligne']);
        self::assertSame(2, $relu[2]['champs']);
        self::assertSame('Active filters\\:', $relu[1]['cle']);
    }

    public function testLireCompteLesChampsEtIgnoreLesLignesVides(): void
    {
        file_put_contents($this->fichier, "\"a\",\"b\",\"c\"\n\n\"d\",\"e\"\n");
        $relu = Csv::lire($this->fichier);
        self::assertSame(3, $relu[0]['champs']);
        self::assertSame(3, $relu[1]['ligne']);
    }
}
```

- [ ] **Step 5 : lancer, constater l'échec**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && chmod +x outils/php outils/phpunit && outils/phpunit"`
Expected : erreur `Failed opening required '.../src/Csv.php'`.

- [ ] **Step 6 : écrire `outils/src/Csv.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Lecture et ecriture des CSV de traduction au format de Magento : virgule,
 * guillemets doubles, AUCUN caractere d'echappement (les antislashs sont
 * litteraux, comme dans Magento\Framework\File\Csv).
 */
final class Csv
{
    /**
     * @return list<array{cle: string, traduction: string, ligne: int, champs: int}>
     */
    public static function lire(string $fichier): array
    {
        $h = fopen($fichier, 'rb');
        if ($h === false) {
            throw new \RuntimeException("Lecture impossible : $fichier");
        }
        $entrees = [];
        $n = 0;
        while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $n++;
            if ($l === [null] || $l === []) {
                continue;
            }
            $entrees[] = [
                'cle' => (string) $l[0],
                'traduction' => (string) ($l[1] ?? ''),
                'ligne' => $n,
                'champs' => count($l),
            ];
        }
        fclose($h);

        return $entrees;
    }

    /**
     * @param array<array-key, string> $entrees cle => traduction
     */
    public static function ecrire(string $fichier, array $entrees): void
    {
        // Les cles numeriques (« 404 ») deviennent des entiers en PHP : on
        // compare et on ecrit toujours des chaines.
        uksort($entrees, static fn ($a, $b): int => strcmp((string) $a, (string) $b));
        $lignes = '';
        foreach ($entrees as $cle => $traduction) {
            $lignes .= self::champ((string) $cle) . ',' . self::champ($traduction) . "\n";
        }
        if (file_put_contents($fichier, $lignes) === false) {
            throw new \RuntimeException("Ecriture impossible : $fichier");
        }
    }

    private static function champ(string $valeur): string
    {
        return '"' . str_replace('"', '""', $valeur) . '"';
    }
}
```

- [ ] **Step 7 : relancer** — Run : même commande qu'au Step 5. Expected : `OK (2 tests, …)`.

- [ ] **Step 8 : commit**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils && git commit -q -m 'feat(outils): enveloppes Docker et lecture/ecriture CSV au format Magento' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 3 : Règles typographiques et variables

**Files :**
- Create : `outils/src/Typographie.php`, `outils/src/Variables.php`
- Test : `outils/tests/TypographieTest.php`, `outils/tests/VariablesTest.php`

**Interfaces :**
- Produces : `Typographie::NBSP` (`"\u{00A0}"`) ; `Typographie::texteVisible(string): string` ; `Typographie::erreurs(string $traduction): list<array{regle:string, message:string}>` (règles `apostrophe`, `espace-ponctuation`, `guillemets`, `suspension`) ; `Typographie::avertissements(string $traduction): list<array{regle:string, message:string}>` (règles `tutoiement`, `majuscules`). `Variables::extraire(string): list<string>` (triée).

- [ ] **Step 1 : écrire les tests qui échouent** `outils/tests/TypographieTest.php`

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Typographie;
use PHPUnit\Framework\TestCase;

final class TypographieTest extends TestCase
{
    private const N = "\u{00A0}";

    /** @return list<string> */
    private function regles(string $t): array
    {
        return array_column(Typographie::erreurs($t), 'regle');
    }

    public function testUnTexteConformeNaAucuneErreur(): void
    {
        self::assertSame([], $this->regles('L’article est ajouté' . self::N . ': «' . self::N . 'ok' . self::N . '»' . self::N . '!'));
    }

    public function testApostropheDroiteRefuseeMemeDansUneBalise(): void
    {
        self::assertSame(['apostrophe'], $this->regles("L'article"));
        self::assertSame(['apostrophe'], $this->regles("<a href='x'>lien</a>"));
    }

    public function testEspaceNormaleAvantPonctuationDouble(): void
    {
        self::assertSame(['espace-ponctuation'], $this->regles('Total : 5'));
    }

    public function testPonctuationCollee(): void
    {
        self::assertSame(['espace-ponctuation'], $this->regles('Total: 5'));
        self::assertSame(['espace-ponctuation'], $this->regles('Vraiment?'));
    }

    public function testCeQuiNEstPasDuTexteEchappeAuxRegles(): void
    {
        self::assertSame([], $this->regles('Ouvert de 10:30 à 18:00'));
        self::assertSame([], $this->regles('Voir https://exemple.fr?a=b'));
        self::assertSame([], $this->regles('<span style="color:red">Rouge</span>'));
        self::assertSame([], $this->regles('{{var order.getId()}}&nbsp;commande'));
    }

    public function testGuillemetsDroitsHorsBalisage(): void
    {
        self::assertSame(['guillemets'], $this->regles('Cliquez sur "Envoyer"'));
    }

    public function testGuillemetsFrancaisSansInsecable(): void
    {
        self::assertSame(['guillemets'], $this->regles('« Envoyer »'));
    }

    public function testPointsDeSuspension(): void
    {
        self::assertSame(['suspension'], $this->regles('Chargement...'));
    }

    public function testTutoiementSignale(): void
    {
        self::assertSame(['tutoiement'], array_column(Typographie::avertissements('Ton panier est vide'), 'regle'));
        self::assertSame([], Typographie::avertissements('Votre panier est vide'));
    }

    public function testMajusculesDeTitreSignalees(): void
    {
        self::assertSame(['majuscules'], array_column(Typographie::avertissements('Ajouter Au Panier Rapide'), 'regle'));
        self::assertSame([], Typographie::avertissements('Ajouter au panier PayPal'));
    }
}
```

`outils/tests/VariablesTest.php` :

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Variables;
use PHPUnit\Framework\TestCase;

final class VariablesTest extends TestCase
{
    public function testExtraitParametresDirectivesEtBalises(): void
    {
        self::assertSame(
            ['%1', '%s', '</a', '<a', '{{var name}}'],
            Variables::extraire('%1 <a href="x">%s</a> {{var name}}')
        );
    }

    public function testLOrdreNeComptePasMaisLeNombreSi(): void
    {
        self::assertSame(Variables::extraire('%2 de %1'), Variables::extraire('%1 of %2'));
        self::assertNotSame(Variables::extraire('%1'), Variables::extraire('%1 %1'));
    }

    public function testLesAttributsDesBalisesNeComptentPas(): void
    {
        self::assertSame(Variables::extraire('<b class="x">a</b>'), Variables::extraire('<B>a</b>'));
    }
}
```

- [ ] **Step 2 : lancer, constater l'échec** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/phpunit"`. Expected : `Failed opening required '.../src/Typographie.php'`.

- [ ] **Step 3 : écrire `outils/src/Typographie.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Regles typographiques d'une traduction. Elles ne portent que sur le texte
 * VISIBLE : balises HTML, directives {{...}} et entites &...; en sont retirees,
 * sauf pour l'apostrophe, interdite partout (une apostrophe droite dans un
 * attribut casse aussi Knockout).
 */
final class Typographie
{
    public const NBSP = "\u{00A0}";

    public static function texteVisible(string $s): string
    {
        $s = (string) preg_replace('/\{\{.*?\}\}/su', ' ', $s);
        $s = (string) preg_replace('/<[^>]*>/su', ' ', $s);

        return (string) preg_replace('/&[a-zA-Z]+;|&#\d+;/u', ' ', $s);
    }

    /** @return list<array{regle: string, message: string}> */
    public static function erreurs(string $traduction): array
    {
        $e = [];
        $t = self::texteVisible($traduction);

        if (str_contains($traduction, "'")) {
            $e[] = ['regle' => 'apostrophe', 'message' => "apostrophe droite ' : utiliser ’ (U+2019)"];
        }
        if (preg_match('/ [:;!?]/u', $t) || preg_match('/\p{L}[:;!?](?=\s|$)/u', $t)) {
            $e[] = ['regle' => 'espace-ponctuation', 'message' => 'avant : ; ! ? il faut une espace insécable (U+00A0)'];
        }
        if (str_contains($t, '"') || preg_match('/«(?!\x{00A0})|(?<!\x{00A0})»/u', $t)) {
            $e[] = ['regle' => 'guillemets', 'message' => 'guillemets : « » avec insécables, " seulement dans le balisage'];
        }
        if (str_contains($t, '...')) {
            $e[] = ['regle' => 'suspension', 'message' => '... : utiliser … (U+2026)'];
        }

        return $e;
    }

    /** @return list<array{regle: string, message: string}> */
    public static function avertissements(string $traduction): array
    {
        $a = [];
        $t = self::texteVisible($traduction);

        if (preg_match('/(?<!\p{L})(tu|toi|ton|ta|tes)(?!\p{L})/iu', $t)) {
            $a[] = ['regle' => 'tutoiement', 'message' => 'tutoiement : le vouvoiement est la règle côté client'];
        }

        // Majuscules de titre : au moins deux mots, hors le premier, qui
        // commencent par une majuscule sans etre des sigles ni des marques
        // en casse mixte (PayPal).
        $mots = preg_split('/\s+/u', trim($t)) ?: [];
        $titres = 0;
        foreach (array_slice($mots, 1) as $mot) {
            if (preg_match('/^\p{Lu}\p{Ll}+$/u', $mot)) {
                $titres++;
            }
        }
        if ($titres >= 2) {
            $a[] = ['regle' => 'majuscules', 'message' => 'majuscules de titre : en français, seule la première lettre de la phrase'];
        }

        return $a;
    }
}
```

- [ ] **Step 4 : écrire `outils/src/Variables.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Ce qui doit se retrouver a l'identique entre la cle et sa traduction :
 * parametres %1 %s %d, directives {{...}}, et noms de balises HTML.
 */
final class Variables
{
    /** @return list<string> */
    public static function extraire(string $s): array
    {
        preg_match_all('/%\d+|%[sd]|\{\{.*?\}\}|<\/?[a-zA-Z][a-zA-Z0-9]*/s', $s, $m);
        $v = array_map(
            static fn (string $x): string => $x[0] === '<' ? strtolower($x) : $x,
            $m[0]
        );
        sort($v, SORT_STRING);

        return $v;
    }
}
```

- [ ] **Step 5 : relancer** — Run : même commande qu'au Step 2. Expected : `OK (15 tests, …)` (2 de Csv + 10 + 3).

- [ ] **Step 6 : commit** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils && git commit -q -m 'feat(outils): regles typographiques et controle des variables' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 4 : Vérificateur et commande `verifier.php`

**Files :**
- Create : `outils/src/Constat.php`, `outils/src/Verificateur.php`, `outils/verifier.php`, `outils/telecharger-communautaire.php`, `outils/communautaire.version`, `outils/glossaire.csv`, `outils/interdites.txt`, `outils/identiques-autorises.txt`
- Test : `outils/tests/VerificateurTest.php`

**Interfaces :**
- Consumes : `Csv::lire`, `Typographie::erreurs`, `Typographie::avertissements`, `Variables::extraire`.
- Produces : `Constat` (propriétés publiques en lecture seule `niveau` `'erreur'|'avertissement'`, `fichier`, `ligne`, `regle`, `message`) ; `Verificateur::__construct(array $communautaire, list<string> $interdites, list<string> $identiquesAutorises, array<string,string> $glossaire)` ; `Verificateur::verifierPack(array<string, string> $fichiers /* nom => chemin */): list<Constat>`. Commande `outils/php outils/verifier.php` (code 1 si au moins une erreur). Fichier `outils/.cache/communautaire.csv`.

- [ ] **Step 1 : écrire les fichiers de référence**

`outils/communautaire.version` :
```
0.0.64
```

`outils/interdites.txt` (une clé par ligne ; commentaires `#`) :
```
# Chaines comparees ou affectees en dur dans du JavaScript.
# Ne les traduire qu'apres correction du code par maxcode/module-translation-fixes.
Manage Gallery
```

`outils/identiques-autorises.txt` :
```
# Traductions identiques a l'anglais autorisees : noms propres et sigles.
PayPal
SKU
URL
SEO
SMTP
API
ID
IP
CSV
PDF
XML
JSON
HTML
CSS
Google
Facebook
Instagram
Amazon
Klarna
```

`outils/glossaire.csv` (termes du pack communautaire 0.0.61, relevés le 01/10/2026 ; clé anglaise en minuscules) :
```
"add to cart","ajouter au panier"
"attribute","attribut"
"category","catégorie"
"coupon","bon de réduction"
"credit memo","avoir"
"customer","client"
"discount","remise"
"grand total","montant global"
"invoice","facture"
"my account","mon compte"
"order","commande"
"product","produit"
"quantity","quantité"
"quote","devis"
"settings","paramètres"
"shipping","livraison"
"shopping cart","panier"
"store view","vue magasin"
"subtotal","sous-total"
"website","site web"
```

- [ ] **Step 2 : écrire le test qui échoue** `outils/tests/VerificateurTest.php`

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Verificateur;
use PHPUnit\Framework\TestCase;

final class VerificateurTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir() . '/pack-' . uniqid();
        mkdir($this->dossier);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dossier . '/*') ?: []);
        rmdir($this->dossier);
    }

    private function fichier(string $nom, string $contenu): string
    {
        file_put_contents($this->dossier . '/' . $nom, $contenu);

        return $this->dossier . '/' . $nom;
    }

    private function verificateur(): Verificateur
    {
        return new Verificateur(
            ['Translated by community' => 'Traduit par la communauté', 'Untranslated' => 'Untranslated'],
            ['Manage Gallery'],
            ['PayPal'],
            ['shopping cart' => 'panier']
        );
    }

    /** @return list<string> */
    private function regles(array $fichiers, string $niveau = 'erreur'): array
    {
        $r = [];
        foreach ($this->verificateur()->verifierPack($fichiers) as $c) {
            if ($c->niveau === $niveau) {
                $r[] = $c->regle;
            }
        }
        sort($r);

        return $r;
    }

    public function testUnPackConformeNaAucuneErreur(): void
    {
        $f = $this->fichier('core.csv', "\"Untranslated\",\"Non traduit\"\n\"PayPal\",\"PayPal\"\n");
        self::assertSame([], $this->regles(['core.csv' => $f]));
    }

    public function testLesErreursDeContenu(): void
    {
        $f = $this->fichier('core.csv',
            "\"Translated by community\",\"Autre chose\"\n"   // communautaire
            . "\"Same\",\"Same\"\n"                           // identique
            . "\"Empty\",\"\"\n"                              // vide
            . "\"Manage Gallery\",\"Gérer la galerie\"\n"     // interdite
            . "\"%1 items\",\"articles\"\n"                   // variables
            . "\"A\",\"B\",\"C\"\n"                           // format
        );
        self::assertSame(
            ['communautaire', 'format', 'identique', 'interdite', 'variables', 'vide'],
            $this->regles(['core.csv' => $f])
        );
    }

    public function testDoublonDansUnFichierEtEntreFichiers(): void
    {
        $a = $this->fichier('amasty.csv', "\"Hello\",\"Bonjour\"\n\"Hello\",\"Salut\"\n");
        $b = $this->fichier('core.csv', "\"Hello\",\"Bonjour\"\n");
        self::assertSame(['doublon', 'doublon-fichiers'], $this->regles(['amasty.csv' => $a, 'core.csv' => $b]));
    }

    public function testLeFichierLuiMeme(): void
    {
        $bom = $this->fichier('core.csv', "\xEF\xBB\xBF\"A\",\"B\"\r\n");    // BOM + \r
        self::assertSame(['fichier', 'fichier'], $this->regles(['core.csv' => $bom]));
        $sansFin = $this->fichier('amasty.csv', '"C","D"');               // pas de \n final
        self::assertSame(['fichier'], $this->regles(['amasty.csv' => $sansFin]));
    }

    public function testTypographieEtGlossaire(): void
    {
        $f = $this->fichier('core.csv', "\"Shopping cart\",\"L'endroit\"\n");
        self::assertSame(['apostrophe'], $this->regles(['core.csv' => $f]));
        self::assertSame(['glossaire'], $this->regles(['core.csv' => $f], 'avertissement'));
    }
}
```

- [ ] **Step 3 : lancer, constater l'échec** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/phpunit"`. Expected : `Failed opening required '.../src/Verificateur.php'`.

- [ ] **Step 4 : écrire `outils/src/Constat.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

final class Constat
{
    public function __construct(
        public readonly string $niveau,
        public readonly string $fichier,
        public readonly int $ligne,
        public readonly string $regle,
        public readonly string $message,
    ) {
    }
}
```

- [ ] **Step 5 : écrire `outils/src/Verificateur.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Applique les regles du pack (spec § 5). Une erreur bloque la publication ;
 * un avertissement signale ce qui merite un regard humain.
 */
final class Verificateur
{
    /**
     * @param array<string, string> $communautaire cle => traduction du pack communautaire
     * @param list<string> $interdites
     * @param list<string> $identiquesAutorises
     * @param array<string, string> $glossaire terme anglais (minuscules) => terme francais
     */
    public function __construct(
        private readonly array $communautaire,
        private readonly array $interdites,
        private readonly array $identiquesAutorises,
        private readonly array $glossaire,
    ) {
    }

    /**
     * @param array<string, string> $fichiers nom => chemin
     * @return list<Constat>
     */
    public function verifierPack(array $fichiers): array
    {
        $constats = [];
        $vuesPack = [];

        foreach ($fichiers as $nom => $chemin) {
            array_push($constats, ...$this->verifierOctets($nom, $chemin));
            $vuesFichier = [];

            foreach (Csv::lire($chemin) as $e) {
                [$cle, $trad, $ligne] = [$e['cle'], $e['traduction'], $e['ligne']];
                $erreur = static fn (string $regle, string $message): Constat
                    => new Constat('erreur', $nom, $ligne, $regle, $message . ' — « ' . mb_substr($cle, 0, 60) . ' »');

                if ($e['champs'] !== 2) {
                    $constats[] = $erreur('format', sprintf('%d champ(s) au lieu de 2 (guillemet non doublé ?)', $e['champs']));
                    continue;
                }
                if (isset($vuesFichier[$cle])) {
                    $constats[] = $erreur('doublon', 'clé déjà présente ligne ' . $vuesFichier[$cle]);
                } elseif (isset($vuesPack[$cle])) {
                    $constats[] = $erreur('doublon-fichiers', 'clé déjà présente dans ' . $vuesPack[$cle]);
                }
                $vuesFichier[$cle] = $ligne;
                $vuesPack[$cle] ??= $nom;

                if (trim($trad) === '') {
                    $constats[] = $erreur('vide', 'traduction vide');
                    continue;
                }
                if (isset($this->communautaire[$cle]) && $this->communautaire[$cle] !== $cle) {
                    $constats[] = $erreur('communautaire', 'déjà traduite par le pack communautaire');
                }
                if ($trad === $cle && !in_array($cle, $this->identiquesAutorises, true)) {
                    $constats[] = $erreur('identique', 'traduction identique à l’anglais');
                }
                if (in_array($cle, $this->interdites, true)) {
                    $constats[] = $erreur('interdite', 'chaîne comparée en dur dans du JavaScript');
                }
                if (Variables::extraire($cle) !== Variables::extraire($trad)) {
                    $constats[] = $erreur('variables', 'paramètres, directives ou balises différents');
                }
                foreach (Typographie::erreurs($trad) as $t) {
                    $constats[] = $erreur($t['regle'], $t['message']);
                }
                foreach (Typographie::avertissements($trad) as $t) {
                    $constats[] = new Constat('avertissement', $nom, $ligne, $t['regle'], $t['message'] . ' — « ' . mb_substr($trad, 0, 60) . ' »');
                }
                foreach ($this->glossaire as $en => $fr) {
                    $normalise = str_replace('’', "'", mb_strtolower($trad));
                    if (preg_match('/(?<!\p{L})' . preg_quote($en, '/') . '(?!\p{L})/iu', $cle)
                        && !str_contains($normalise, str_replace('’', "'", $fr))) {
                        $constats[] = new Constat('avertissement', $nom, $ligne, 'glossaire',
                            sprintf('« %s » se traduit « %s » dans le pack communautaire — « %s »', $en, $fr, mb_substr($cle, 0, 60)));
                    }
                }
            }
        }

        return $constats;
    }

    /** @return list<Constat> */
    private function verifierOctets(string $nom, string $chemin): array
    {
        $brut = (string) file_get_contents($chemin);
        $c = [];
        if (str_starts_with($brut, "\xEF\xBB\xBF")) {
            $c[] = new Constat('erreur', $nom, 1, 'fichier', 'BOM UTF-8 à retirer');
        }
        if (!mb_check_encoding($brut, 'UTF-8')) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'encodage autre que UTF-8');
        }
        if (str_contains($brut, "\r")) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'fins de ligne Windows (\r)');
        }
        if ($brut !== '' && !str_ends_with($brut, "\n")) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'pas de saut de ligne final');
        }

        return $c;
    }
}
```

- [ ] **Step 6 : relancer** — Run : même commande qu'au Step 3. Expected : `OK (20 tests, …)`.

- [ ] **Step 7 : écrire `outils/telecharger-communautaire.php`**

```php
<?php
declare(strict_types=1);

/**
 * Telecharge le fr_FR.csv du pack communautaire, a la version de
 * outils/communautaire.version, depuis sa source publique (sans identifiants
 * Magento) : Packagist donne le commit, GitHub le fichier.
 */
$version = trim((string) file_get_contents(__DIR__ . '/communautaire.version'));
$meta = json_decode((string) file_get_contents('https://repo.packagist.org/p2/community-engineering/language-fr_fr.json'), true);
// Format p2 « minifie » : chaque version ne porte que ce qui change par
// rapport a la precedente, il faut cumuler.
$entree = null;
$courante = [];
foreach ($meta['packages']['community-engineering/language-fr_fr'] ?? [] as $v) {
    $courante = array_merge($courante, $v);
    if (ltrim((string) $courante['version'], 'v') === $version) {
        $entree = $courante;
        break;
    }
}
if ($entree === null) {
    fwrite(STDERR, "Version $version introuvable sur Packagist.\n");
    exit(1);
}
$ref = $entree['source']['reference'];
$csv = file_get_contents("https://raw.githubusercontent.com/magento-l10n/language-fr_FR/$ref/fr_FR.csv");
if ($csv === false || $csv === '') {
    fwrite(STDERR, "Téléchargement impossible (commit $ref).\n");
    exit(1);
}
@mkdir(__DIR__ . '/.cache', 0775, true);
file_put_contents(__DIR__ . '/.cache/communautaire.csv', $csv);
printf("Pack communautaire %s (commit %s) : %d lignes, sha256 %s\n",
    $version, substr($ref, 0, 10), substr_count($csv, "\n"), substr(hash('sha256', $csv), 0, 16));
```

- [ ] **Step 8 : écrire `outils/verifier.php`**

```php
<?php
declare(strict_types=1);

/**
 * Controle tous les CSV a la racine du pack. Code de sortie 1 si au moins une
 * erreur. Usage : outils/php outils/verifier.php [--communautaire=<fichier>]
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Verificateur;

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
```

- [ ] **Step 9 : essai réel (pack encore vide)**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/telecharger-communautaire.php && outils/php outils/verifier.php"`
Expected : `Pack communautaire 0.0.64 (commit …) : 1xxxx lignes, sha256 …`, puis `0 fichier(s), 0 entrée(s) : 0 erreur(s), 0 avertissement(s).` et code 0.

- [ ] **Step 10 : commit** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils && git commit -q -m 'feat(outils): verificateur bloquant, telechargement du pack communautaire, references' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 5 : Normaliseur typographique

**Files :**
- Create : `outils/src/Normaliseur.php`, `outils/normaliser.php`
- Test : `outils/tests/NormaliseurTest.php`

**Interfaces :**
- Consumes : `Typographie::NBSP`, `Csv`.
- Produces : `Normaliseur::normaliser(string $traduction): string` ; commande `outils/php outils/normaliser.php <fichier.csv>` (réécrit le fichier).

- [ ] **Step 1 : écrire le test qui échoue** `outils/tests/NormaliseurTest.php`

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Normaliseur;
use Maxcode\LanguagePack\Outils\Typographie;
use PHPUnit\Framework\TestCase;

final class NormaliseurTest extends TestCase
{
    private const N = "\u{00A0}";

    public function testCorrigeLeTexteVisible(): void
    {
        self::assertSame('L’article' . self::N . ': ok' . self::N . '!', Normaliseur::normaliser("L'article : ok!"));
        self::assertSame('Chargement…', Normaliseur::normaliser('Chargement...'));
        self::assertSame('Cliquez sur «' . self::N . 'Envoyer' . self::N . '»', Normaliseur::normaliser('Cliquez sur "Envoyer"'));
        self::assertSame('«' . self::N . 'Envoyer' . self::N . '»', Normaliseur::normaliser('« Envoyer »'));
    }

    public function testNeTouchePasAuBalisageNiAuxDirectives(): void
    {
        $s = '<a href="x" style="color:red">Lien</a> {{var a}} 10:30';
        self::assertSame($s, Normaliseur::normaliser($s));
    }

    public function testLeResultatPasseLesControles(): void
    {
        self::assertSame([], Typographie::erreurs(Normaliseur::normaliser("Vraiment? L'aide... \"ici\" : oui")));
    }

    public function testIdempotent(): void
    {
        $une = Normaliseur::normaliser("L'aide : « ok »...");
        self::assertSame($une, Normaliseur::normaliser($une));
    }
}
```

- [ ] **Step 2 : lancer, constater l'échec** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/phpunit"`. Expected : `Failed opening required '.../src/Normaliseur.php'`.

- [ ] **Step 3 : écrire `outils/src/Normaliseur.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Corrige automatiquement la typographie du TEXTE VISIBLE d'une traduction ;
 * balises, directives {{...}} et entites sont laissees intactes. Idempotent.
 */
final class Normaliseur
{
    public static function normaliser(string $t): string
    {
        $segments = preg_split('/(<[^>]*>|\{\{.*?\}\}|&[a-zA-Z]+;|&#\d+;)/su', $t, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$t];
        foreach ($segments as $i => $s) {
            if ($i % 2 === 1) {
                continue; // balisage capture : intact
            }
            $s = str_replace("'", '’', $s);
            $s = str_replace('...', '…', $s);
            $s = (string) preg_replace('/"([^"]*)"/u', '«' . Typographie::NBSP . '$1' . Typographie::NBSP . '»', $s);
            $s = (string) preg_replace('/«[ \x{00A0}]*/u', '«' . Typographie::NBSP, $s);
            $s = (string) preg_replace('/[ \x{00A0}]*»/u', Typographie::NBSP . '»', $s);
            $s = (string) preg_replace('/ ([:;!?])/u', Typographie::NBSP . '$1', $s);
            $s = (string) preg_replace('/(\p{L})([:;!?])(?=\s|$)/u', '$1' . Typographie::NBSP . '$2', $s);
            $segments[$i] = $s;
        }

        return implode('', $segments);
    }
}
```

- [ ] **Step 4 : écrire `outils/normaliser.php`**

```php
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
```

- [ ] **Step 5 : relancer** — Run : même commande qu'au Step 2. Expected : `OK (24 tests, …)`.

- [ ] **Step 6 : commit** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils && git commit -q -m 'feat(outils): normaliseur typographique idempotent' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 6 : Classement par éditeur et intégration des listes de travail

**Files :**
- Create : `outils/src/Classement.php`, `outils/src/Integration.php`, `outils/integrer.php`
- Test : `outils/tests/ClassementTest.php`, `outils/tests/IntegrationTest.php`

**Interfaces :**
- Produces : `Classement::editeur(string $composant): string` (`'Magento_Catalog'` → `'core'`, `'Amasty_Base'` → `'amasty'`, `'frontend/Smartwave/porto'` → `'smartwave'`, `'adminhtml/Magento/backend'` → `'core'`) ; `Classement::exclu(string $composant): bool` (vrai pour `Maxcode_*`). `Integration::integrer(array $existant, list<array{cle:string,traduction:string,module:string,zone:string}> $lignes, array $autresFichiers, array $communautaire): array{entrees: array<string,string>, front: list<string>, refusees: list<array{cle:string, raison:string}>}`. Commande `outils/php outils/integrer.php <editeur>`. Format d'une liste de travail `outils/a-traduire/<editeur>.csv` : 4 colonnes `cle, traduction, module, zone` (`zone` ∈ `front`, `admin`).

- [ ] **Step 1 : écrire les tests qui échouent**

`outils/tests/ClassementTest.php` :
```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Classement;
use PHPUnit\Framework\TestCase;

final class ClassementTest extends TestCase
{
    public function testModulesEtThemes(): void
    {
        self::assertSame('core', Classement::editeur('Magento_Catalog'));
        self::assertSame('amasty', Classement::editeur('Amasty_Base'));
        self::assertSame('mageworx', Classement::editeur('MageWorx_SeoBase'));
        self::assertSame('bss', Classement::editeur('Bss_Gdpr'));
        self::assertSame('smartwave', Classement::editeur('frontend/Smartwave/porto_child'));
        self::assertSame('swissup', Classement::editeur('frontend/Swissup/breeze-evolution'));
        self::assertSame('core', Classement::editeur('adminhtml/Magento/backend'));
        self::assertSame('core', Classement::editeur('lib/web'));
    }

    public function testLesModulesMaxcodeSontExclus(): void
    {
        self::assertTrue(Classement::exclu('Maxcode_SupplierOrder'));
        self::assertFalse(Classement::exclu('Amasty_Base'));
    }
}
```

`outils/tests/IntegrationTest.php` :
```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils\Tests;

use Maxcode\LanguagePack\Outils\Integration;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    public function testIntegreNormaliseEtRefuse(): void
    {
        $r = Integration::integrer(
            ['Already' => 'Déjà'],
            [
                ['cle' => 'Your cart', 'traduction' => "Votre panier ...", 'module' => 'Amasty_Cart', 'zone' => 'front'],
                ['cle' => 'Empty', 'traduction' => '', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
                ['cle' => 'In core', 'traduction' => 'Dans le cœur', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
                ['cle' => 'Community', 'traduction' => 'Communauté', 'module' => 'Amasty_Cart', 'zone' => 'admin'],
            ],
            ['In core' => 'core.csv'],
            ['Community' => 'Communautaire']
        );

        self::assertSame(['Already' => 'Déjà', 'Your cart' => 'Votre panier …'], $r['entrees']);
        self::assertSame(['Your cart'], $r['front']);
        self::assertSame(
            [['cle' => 'In core', 'raison' => 'déjà dans core.csv'], ['cle' => 'Community', 'raison' => 'traduite par le pack communautaire']],
            $r['refusees']
        );
    }
}
```

- [ ] **Step 2 : lancer, constater l'échec** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/phpunit"`. Expected : `Failed opening required '.../src/Classement.php'`.

- [ ] **Step 3 : écrire `outils/src/Classement.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/** Rattache un composant Magento (module, theme, lib) au fichier de son editeur. */
final class Classement
{
    public static function editeur(string $composant): string
    {
        if ($composant === 'lib/web') {
            return 'core';
        }
        if (preg_match('#^(?:frontend|adminhtml)/([^/]+)/#', $composant, $m)) {
            $vendeur = $m[1];
        } else {
            $vendeur = strstr($composant, '_', true) ?: $composant;
        }
        $v = strtolower($vendeur);

        return $v === 'magento' ? 'core' : $v;
    }

    /** Les modules Maxcode portent leurs propres chaines (regle « un module voyage avec ses chaines »). */
    public static function exclu(string $composant): bool
    {
        return str_starts_with($composant, 'Maxcode_');
    }
}
```

- [ ] **Step 4 : écrire `outils/src/Integration.php`**

```php
<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/** Fait entrer les lignes traduites d'une liste de travail dans le fichier d'un editeur. */
final class Integration
{
    /**
     * @param array<string, string> $existant cle => traduction du fichier de l'editeur
     * @param list<array{cle: string, traduction: string, module: string, zone: string}> $lignes
     * @param array<string, string> $autresFichiers cle => nom du fichier qui la porte deja
     * @param array<string, string> $communautaire cle => traduction communautaire
     * @return array{entrees: array<string, string>, front: list<string>, refusees: list<array{cle: string, raison: string}>}
     */
    public static function integrer(array $existant, array $lignes, array $autresFichiers, array $communautaire): array
    {
        $entrees = $existant;
        $front = [];
        $refusees = [];
        foreach ($lignes as $l) {
            if (trim($l['traduction']) === '') {
                continue;
            }
            if (isset($autresFichiers[$l['cle']])) {
                $refusees[] = ['cle' => $l['cle'], 'raison' => 'déjà dans ' . $autresFichiers[$l['cle']]];
                continue;
            }
            if (isset($communautaire[$l['cle']]) && $communautaire[$l['cle']] !== $l['cle']) {
                $refusees[] = ['cle' => $l['cle'], 'raison' => 'traduite par le pack communautaire'];
                continue;
            }
            $entrees[$l['cle']] = Normaliseur::normaliser($l['traduction']);
            if ($l['zone'] === 'front') {
                $front[] = $l['cle'];
            }
        }

        return ['entrees' => $entrees, 'front' => $front, 'refusees' => $refusees];
    }
}
```

- [ ] **Step 5 : écrire `outils/integrer.php`**

```php
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
```

- [ ] **Step 6 : relancer les tests** — Run : même commande qu'au Step 2. Expected : `OK (27 tests, …)`.

- [ ] **Step 7 : commit** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils && git commit -q -m 'feat(outils): classement par editeur et integration des listes de travail' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 7 : Extraction des chaînes depuis un Magento

**Files :**
- Create : `outils/extraire.php`, `outils/extraction.sh`, `outils/fusionner.php`

**Interfaces :**
- Consumes : `Classement::editeur`, `Classement::exclu`, `Csv`.
- Produces : dans `<site>/var/pack-fr/sortie/` : `index.csv` (toutes les phrases : `cle, module, editeur, zone`), `a-traduire/<editeur>.csv` (4 colonnes `cle, "", module, zone`, hors chaînes déjà traduites), `js-en-dur.txt`. `outils/extraction.sh <racine du site> <nom>` rapatrie dans `outils/a-traduire/sites/<nom>/`. `outils/fusionner.php` produit `outils/a-traduire/<editeur>.csv` (union des sites) et `outils/a-traduire/index.csv`.

- [ ] **Step 1 : écrire `outils/extraire.php`** (s'exécute DANS un Magento, depuis sa racine)

```php
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

$index = [];   // cle => [module, editeur, zone]
$jsEnDur = [];
foreach ($composants as $nom => $c) {
    if (Classement::exclu($nom)) {
        continue;
    }
    $toutes = $phrases($c['chemin']);
    $enUs = $c['chemin'] . '/i18n/en_US.csv';
    if (is_file($enUs)) {
        $toutes = array_merge($toutes, array_column(Csv::lire($enUs), 'cle'));
    }
    $toutes = array_values(array_unique($toutes));
    $front = $c['front'] !== null ? array_flip($phrases($c['front'])) : [];
    if ($c['front'] === $c['chemin']) {
        $front = array_flip($toutes);
    }

    // Lignes JavaScript qui COMPARENT un texte (:contains(, ==, !=) hors $t( :
    // une chaine qu'on y trouve casserait l'ecran une fois traduite (cas
    // « Manage Gallery »). Ce ne sont que des candidates, a trier a la main.
    $lignesJs = [];
    if (is_dir($c['chemin'])) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($c['chemin'], FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.js') && !str_contains($f->getPathname(), '/node_modules/')) {
                foreach (file($f->getPathname(), FILE_IGNORE_NEW_LINES) ?: [] as $ligne) {
                    if (preg_match('/:contains\(|[!=]=/', $ligne) && !str_contains($ligne, '$t(')) {
                        $lignesJs[] = $ligne;
                    }
                }
            }
        }
    }
    $js = implode("\n", $lignesJs);

    foreach ($toutes as $cle) {
        if ($cle === '' || isset($index[$cle])) {
            continue;
        }
        $index[$cle] = [$nom, Classement::editeur($nom), isset($front[$cle]) ? 'front' : 'admin'];
        if ($js !== '' && (str_contains($js, "'" . $cle . "'") || str_contains($js, '"' . $cle . '"'))) {
            $jsEnDur[$cle] = $nom;
        }
    }
}

// Deja traduites : paquets de langue fr_FR installes sur ce site
// (communautaire, et notre copie locale tant qu'elle existe).
$dejaTraduit = [];
foreach ($registrar->getPaths(ComponentRegistrar::LANGUAGE) as $nom => $chemin) {
    if (!str_ends_with(strtolower($nom), '_fr_fr')) {
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
    fputcsv($h, [$cle, $module, $editeur, $zone], ',', '"', '', "\n");
    if (!isset($dejaTraduit[$cle])) {
        $listes[$editeur][] = [$cle, '', $module, $zone];
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
    static fn (string $c, string $m): string => "$m\t$c", array_keys($jsEnDur), $jsEnDur
)) . "\n");

printf("%d phrases, %d déjà traduites, %d comparées dans du JS (à trier), %d à traduire en %d fichier(s).\n",
    count($index), count(array_intersect_key($index, $dejaTraduit)), count($jsEnDur),
    array_sum(array_map('count', $listes)), count($listes));
```

- [ ] **Step 2 : écrire `outils/extraction.sh`** (exécutable)

```bash
#!/usr/bin/env bash
# Lance l'extraction dans le DDEV d'un site et rapatrie le resultat.
# Usage : outils/extraction.sh /home/maxime/projects/ttlx-local ttlx
set -euo pipefail
pack="$(cd "$(dirname "$0")/.." && pwd)"
site="$1"; nom="$2"
rm -rf "$site/var/pack-fr"
mkdir -p "$site/var/pack-fr"
cp -r "$pack/outils" "$site/var/pack-fr/outils"
rm -rf "$site/var/pack-fr/outils/.cache" "$site/var/pack-fr/outils/a-traduire"
(cd "$site" && ddev exec php var/pack-fr/outils/extraire.php)
rm -rf "$pack/outils/a-traduire/sites/$nom"
mkdir -p "$pack/outils/a-traduire/sites"
cp -r "$site/var/pack-fr/sortie" "$pack/outils/a-traduire/sites/$nom"
rm -rf "$site/var/pack-fr"
echo "Extraction de $nom rapatriée dans outils/a-traduire/sites/$nom"
```

- [ ] **Step 3 : écrire `outils/fusionner.php`**

```php
<?php
declare(strict_types=1);

/**
 * Fusionne les extractions des sites (outils/a-traduire/sites/<nom>/) :
 * une liste de travail par editeur (union ; « front » si front sur un site),
 * un index global, et la liste des chaines ecrites en dur dans du JS.
 * Les lignes deja traduites dans une liste existante sont conservees ; les
 * cles deja presentes dans un CSV du pack sont ecartees (a relancer apres la
 * reprise de l'existant).
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;

$base = __DIR__ . '/a-traduire';
$dansLePack = [];
foreach (glob(dirname(__DIR__) . '/*.csv') ?: [] as $f) {
    foreach (Csv::lire($f) as $e) {
        $dansLePack[$e['cle']] = true;
    }
}
$listes = [];
$index = [];
$js = [];
foreach (glob("$base/sites/*", GLOB_ONLYDIR) ?: [] as $site) {
    foreach (glob("$site/a-traduire/*.csv") ?: [] as $f) {
        $editeur = basename($f, '.csv');
        $h = fopen($f, 'rb');
        while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($l === [null] || count($l) < 4 || isset($dansLePack[$l[0]])) {
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
printf("Index : %d phrases ; %d chaînes comparées dans du JS, à trier (outils/a-traduire/js-en-dur.txt).\n", count($index), count($js));
```

- [ ] **Step 4 : essai sur ttlx (test d'intégration)**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && chmod +x outils/extraction.sh && outils/extraction.sh /home/maxime/projects/ttlx-local ttlx && head -3 outils/a-traduire/sites/ttlx/a-traduire/amasty.csv && grep -c '' outils/a-traduire/sites/ttlx/index.csv"`
Expected : une ligne de bilan `N phrases, … à traduire en … fichier(s).` ; trois lignes de `amasty.csv` au format `"clé","","Amasty_…","front|admin"` ; un index de plusieurs milliers de lignes. Vérifier à la main : `grep -c 'Manage Gallery' outils/a-traduire/sites/ttlx/js-en-dur.txt` doit valoir au moins 1 (la détection des comparaisons fonctionne), et la commande doit durer plusieurs minutes sans que la mémoire n'explose (le générateur est bien remis à zéro entre composants).

- [ ] **Step 5 : extraction d'Ambiance et de mojo, puis fusion**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/extraction.sh /home/maxime/projects/ambiance-m2 ambiance && outils/extraction.sh /home/maxime/projects/mojo-m2 mojo && outils/php outils/fusionner.php"`
Expected : un tableau `editeur / à traduire` dont le total est de l'ordre de 7 000 à 10 000 ; `Index : … phrases`.

- [ ] **Step 6 : trier les chaînes comparées dans du JS** — pour chaque ligne `Module<TAB>clé` de `outils/a-traduire/js-en-dur.txt`, lire la ligne de JS qui la contient (Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && grep -rnF --include=*.js \"'<clé>'\" vendor app/code lib/web | grep -E ':contains\(|[!=]='"`). Si le texte anglais est **comparé** au texte affiché (`:contains(`, `=== 'clé'`, `== title`…) et que l'écran affiche la version traduite, ajouter la clé à `outils/interdites.txt` avec une ligne de commentaire `# <Module> <fichier.js>:<ligne>`. Sinon (comparaison d'un code, d'une valeur interne), l'ignorer. Chaque clé interdite est un correctif candidat pour le compagnon (à proposer à l'utilisateur, hors de ce plan). Commit : `chore(outils): chaines comparees dans du JS`.

- [ ] **Step 7 : commit** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils/extraire.php outils/extraction.sh outils/fusionner.php && git commit -q -m 'feat(outils): extraction des chaines depuis un Magento et fusion des sites' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

### Task 8 : Test d'effet, échantillons, CI

**Files :**
- Create : `outils/tester-effet.php`, `outils/echantillon.php`, `.github/workflows/verifier.yml`

**Interfaces :**
- Consumes : `Csv`, `outils/zones/<editeur>.txt`.
- Produces : `php var/pack-fr/outils/tester-effet.php` (dans un site, code 1 en cas d'échec) ; `outils/php outils/echantillon.php <editeur>` → `outils/echantillons/<editeur>-<AAAAMMJJ>.md`.

- [ ] **Step 1 : écrire `outils/tester-effet.php`**

```php
<?php
declare(strict_types=1);

/**
 * Prouve, dans un Magento installe, que le pack est lu et qu'il l'emporte.
 * Usage (racine Magento, pack installe par Composer) :
 *     php var/pack-fr/outils/tester-effet.php
 * Controles :
 *   1. maxcode_fr_fr est enregistre, depuis vendor/ (pas une copie locale) ;
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

$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$langues = (new ComponentRegistrar())->getPaths(ComponentRegistrar::LANGUAGE);
$echecs = 0;

$pack = $langues['maxcode_fr_fr'] ?? null;
printf("maxcode_fr_fr : %s\n", $pack ?? 'NON ENREGISTRÉ');
if ($pack === null || !str_contains($pack, '/vendor/maxcode/language-fr_fr')) {
    echo "ÉCHEC : le pack doit venir de vendor/maxcode/language-fr_fr (copie locale restante ?)\n";
    exit(1);
}

$communautaire = [];
foreach ($langues as $nom => $chemin) {
    if ($nom !== 'maxcode_fr_fr' && str_contains($chemin, 'community-engineering')) {
        foreach (glob("$chemin/*.csv") ?: [] as $f) {
            foreach (Csv::lire($f) as $e) {
                $communautaire[$e['cle']] = $e['traduction'];
            }
        }
    }
}
printf("pack communautaire installé : %d entrées\n", count($communautaire));

$fichiers = glob("$pack/*.csv") ?: [];
$temoins = [];
foreach ($fichiers as $f) {
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

foreach (['frontend', 'adminhtml'] as $aire) {
    $t = $om->create(\Magento\Framework\Translate::class);
    $t->setLocale('fr_FR')->loadData($aire, true);
    $donnees = $t->getData();
    foreach ($temoins as $fichier => $e) {
        $obtenu = $donnees[$e['cle']] ?? '(absente)';
        $ok = $obtenu === $e['traduction'];
        printf("%-10s %-16s %-4s « %s » → « %s »\n", $aire, $fichier, $ok ? 'OK' : 'ÉCHEC',
            mb_substr($e['cle'], 0, 40), mb_substr($obtenu, 0, 40));
        $echecs += (int) !$ok;
    }
}

echo $echecs === 0 ? "\nLe pack est chargé et l'emporte.\n" : "\nÉCHEC : $echecs contrôle(s).\n";
exit($echecs === 0 ? 0 : 1);
```

- [ ] **Step 2 : écrire `outils/echantillon.php`**

```php
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
mt_srand(crc32($editeur . date('Ymd')));
shuffle($admin);
$admin = array_slice($admin, 0, $nombre);

$cellule = static fn (string $s): string => str_replace(['|', "\n"], ['\|', ' '], $s);
$md = "# Relecture — $editeur (" . date('d/m/Y') . ")\n\n";
$md .= sprintf("Parcours client : %d chaîne(s), toutes. Administration : %d sur %d.\n", count($enFront), count($admin), count(Csv::lire($fichier)) - count($enFront));
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
```

- [ ] **Step 3 : écrire `.github/workflows/verifier.yml`**

```yaml
name: Vérification du pack

on:
  push:
  pull_request:

jobs:
  verifier:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: phpunit:10
      - name: Tests des outils
        run: phpunit -c outils/phpunit.xml
      - name: Pack communautaire de référence
        run: php outils/telecharger-communautaire.php
      - name: Règles du pack
        run: php outils/verifier.php
```

- [ ] **Step 4 : contrôle local des trois** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/phpunit && outils/php outils/verifier.php; echo code=\$?"`. Expected : `OK (27 tests, …)`, puis `0 fichier(s) …` et `code=0`. (`tester-effet.php` et `echantillon.php` sont exercés aux tâches 12 et suivantes, une fois le pack peuplé et installé.)

- [ ] **Step 5 : commit et push, puis vérifier la CI** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add outils .github && git commit -q -m 'feat(outils): test d effet, fiches de relecture, CI GitHub Actions' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && git log --oneline -1"`. Puis demander à l'utilisateur de confirmer l'onglet Actions du dépôt : exécution « Vérification du pack » **verte**.

---

## Phase 0 bis — Module compagnon

### Task 9 : Squelette du compagnon et correctif « Login as Customer »

**Files :**
- Create : `module-translation-fixes/{composer.json, registration.php, LICENSE, README.md, CHANGELOG.md, etc/module.xml, etc/adminhtml/di.xml, Plugin/LoginAsCustomer/TraduireLibelleBouton.php}`
- Test : `module-translation-fixes/Test/Unit/Plugin/LoginAsCustomer/TraduireLibelleBoutonTest.php`

**Interfaces :**
- Produces : `Maxcode\TranslationFixes\Plugin\LoginAsCustomer\TraduireLibelleBouton::afterGetData(object $subject, array $result): array` — `$result['label']` chaîne → `Magento\Framework\Phrase`. Paquet installé dans ttlx-local en `source` (branche `feature/pack-traduction-fr`).

- [ ] **Step 1 : écrire `composer.json`, `registration.php`, `etc/module.xml`**

```json
{
    "name": "maxcode/module-translation-fixes",
    "description": "Rend traduisibles les chaînes que Magento n’expose pas à la traduction. Ne contient aucun texte : les traductions viennent des packs de langue.",
    "type": "magento2-module",
    "license": "MIT",
    "authors": [
        {"name": "eBusiness360 – Maxime LESGUILLIER", "homepage": "https://github.com/eBusiness360"}
    ],
    "require": {
        "php": ">=8.1",
        "magento/framework": "^103.0"
    },
    "autoload": {
        "files": ["registration.php"],
        "psr-4": {"Maxcode\\TranslationFixes\\": ""}
    }
}
```

```php
<?php
/**
 * maxcode/module-translation-fixes — MIT, Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Maxcode_TranslationFixes', __DIR__);
```

`etc/module.xml` — pas de `<sequence>` sur les modules corrigés : un site qui les a retirés ne doit pas être bloqué.
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Maxcode_TranslationFixes"/>
</config>
```

`LICENSE` : texte MIT identique à celui de la tâche 1, Step 5.
`README.md` :
```markdown
# Correctifs de traduction pour Magento 2

Rend traduisibles des chaînes que Magento affiche sans les passer par `__()` ou `$t()`.
**Ne contient aucune traduction** : celles-ci viennent de votre pack de langue
(par exemple [`maxcode/language-fr_fr`](https://github.com/eBusiness360/language-fr_fr)).

| Correctif | Chaînes rendues traduisibles |
|---|---|
| Bouton « Login as Customer » (fiche client, commande) | `Login as Customer` |
| Galerie média du back-office | `Manage Gallery`, `Media Gallery` (et répare le mode « Supprimer des images » une fois traduites) |

    composer require maxcode/module-translation-fixes
    bin/magento setup:upgrade

Licence MIT — © 2026 eBusiness360 – Maxime LESGUILLIER.
```
`CHANGELOG.md` :
```markdown
# Changelog — maxcode/module-translation-fixes

## [Non publié]

### Ajouté
- Libellé du bouton « Login as Customer » passé par `__()`.
```

- [ ] **Step 2 : écrire le test qui échoue** `Test/Unit/Plugin/LoginAsCustomer/TraduireLibelleBoutonTest.php`

```php
<?php
declare(strict_types=1);

namespace Maxcode\TranslationFixes\Test\Unit\Plugin\LoginAsCustomer;

use Magento\Framework\Phrase;
use Maxcode\TranslationFixes\Plugin\LoginAsCustomer\TraduireLibelleBouton;
use PHPUnit\Framework\TestCase;

final class TraduireLibelleBoutonTest extends TestCase
{
    public function testLeLibelleDevientUnePhraseTraduisible(): void
    {
        $r = (new TraduireLibelleBouton())->afterGetData(new \stdClass(), ['label' => 'Login as Customer', 'on_click' => 'x()']);

        self::assertInstanceOf(Phrase::class, $r['label']);
        self::assertSame('Login as Customer', $r['label']->getText());
        self::assertSame('x()', $r['on_click']);
    }

    public function testSansLibelleRienNeChange(): void
    {
        self::assertSame(['on_click' => 'x()'], (new TraduireLibelleBouton())->afterGetData(new \stdClass(), ['on_click' => 'x()']));
    }

    public function testUnLibelleDejaPhraseNestPasRetouche(): void
    {
        $phrase = new Phrase('Login as Customer');
        $r = (new TraduireLibelleBouton())->afterGetData(new \stdClass(), ['label' => $phrase]);
        self::assertSame($phrase, $r['label']);
    }
}
```

- [ ] **Step 3 : créer le dépôt, l'installer dans ttlx (branche dédiée), constater l'échec**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/module-translation-fixes && git init -q -b main && git config user.name 'Maxime LESGUILLIER' && git config user.email 'maxime@ebusiness-atlantique.fr' && git add -A && git commit -q -m 'chore: squelette du module compagnon (MIT)' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git remote add origin git@github.com:eBusiness360/module-translation-fixes.git && git push -q -u origin main && cd /home/maxime/projects/ttlx-local && git checkout -q -b feature/pack-traduction-fr && ddev composer config repositories.maxcode-translation-fixes vcs git@github.com:eBusiness360/module-translation-fixes.git && ddev composer config preferred-install.maxcode/module-translation-fixes source && ddev composer require 'maxcode/module-translation-fixes:dev-main' --no-interaction 2>&1 | tail -3 && ddev exec vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/maxcode/module-translation-fixes/Test/Unit 2>&1 | tail -3"`
Expected : installation `Cloning …`, puis PHPUnit en erreur `Class "Maxcode\TranslationFixes\Plugin\LoginAsCustomer\TraduireLibelleBouton" not found`.

(Le dépôt installé dans `vendor/maxcode/module-translation-fixes` est une copie git : y développer directement, puis `git push` depuis ce dossier. Ne pas lancer de `composer update` sur ce paquet avec des modifications non poussées.)

- [ ] **Step 4 : écrire `Plugin/LoginAsCustomer/TraduireLibelleBouton.php`** (dans `ttlx-local/vendor/maxcode/module-translation-fixes/`)

```php
<?php
/**
 * maxcode/module-translation-fixes — MIT, Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 */
declare(strict_types=1);

namespace Maxcode\TranslationFixes\Plugin\LoginAsCustomer;

use Magento\Framework\Phrase;

/**
 * Le libelle « Login as Customer » est declare translatable="true" dans le
 * di.xml de Magento_LoginAsCustomerAdminUi, mais dans un TABLEAU d'arguments :
 * l'interpreteur DI ne l'enveloppe pas dans une Phrase, il arrive au gabarit
 * en chaine brute et aucun CSV ne peut le traduire. On l'enveloppe ici.
 *
 * Plugin sur Magento\LoginAsCustomerAdminUi\Ui\Customer\Component\Button\DataProvider::getData() ;
 * $subject non type : le module cible peut etre absent (replace Composer).
 */
final class TraduireLibelleBouton
{
    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function afterGetData(object $subject, array $result): array
    {
        if (isset($result['label']) && is_string($result['label'])) {
            $result['label'] = new Phrase($result['label']);
        }

        return $result;
    }
}
```

`etc/adminhtml/di.xml` :
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <!-- Declare sur le type : les deux virtualType du module cible en heritent. -->
    <type name="Magento\LoginAsCustomerAdminUi\Ui\Customer\Component\Button\DataProvider">
        <plugin name="maxcode_translation_fixes_login_as_customer_label"
                type="Maxcode\TranslationFixes\Plugin\LoginAsCustomer\TraduireLibelleBouton"/>
    </type>
</config>
```

- [ ] **Step 5 : relancer les tests** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && ddev exec vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/maxcode/module-translation-fixes/Test/Unit 2>&1 | tail -2"`. Expected : `OK (3 tests, 5 assertions)`.

- [ ] **Step 6 : vérification dans Magento** — retirer temporairement de ttlx le CoreTranslation qui écrit le libellé en dur, sinon le test ne prouve rien :

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && ddev exec php bin/magento module:disable Maxcode_CoreTranslation && ddev exec php bin/magento module:enable Maxcode_TranslationFixes && ddev exec php bin/magento setup:upgrade 2>&1 | tail -1 && ddev exec php bin/magento setup:di:compile 2>&1 | tail -1 && ddev exec php bin/magento cache:flush >/dev/null"`
Expected : `Upgrade completed successfully.` puis `Generated code and dependency injection configuration successfully.`

Puis, dans le navigateur intégré, sur `https://ttlx-local.ddev.site/adminpanel` (compte de test créé par `ddev exec php bin/magento admin:user:create` avec des identifiants générés et notés dans `ttlx-local/var/compte-test-admin.txt`, jamais dans la conversation), ouvrir une fiche client : le bouton s'affiche **« Login as Customer »** tant qu'aucun pack ne le traduit (le CSV de CoreTranslation est désactivé) — preuve que la chaîne passe désormais par le dictionnaire ; la tâche 12 ajoutera sa traduction au pack.

- [ ] **Step 7 : commit et push depuis la copie installée** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local/vendor/maxcode/module-translation-fixes && git add -A && git commit -q -m 'feat: libelle du bouton Login as Customer passe par __()' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && git log --oneline -1"`

---

### Task 10 : Correctif de la galerie média (mixin RequireJS)

**Files :**
- Create : `view/adminhtml/requirejs-config.js`, `view/adminhtml/web/js/media-gallery/massaction-view-mixin.js`
- Modify : `README.md` (déjà à jour), `CHANGELOG.md` ; dans le pack : `outils/interdites.txt` (retirer `Manage Gallery`)

**Interfaces :**
- Consumes : `Magento_MediaGalleryUi/js/grid/massaction/massactionView` (défauts `standAloneTitle: 'Manage Gallery'` l. 17, `slidePanelTitle: 'Media Gallery'` l. 18, comparés par `$('h1:contains(...)')` l. 90 et 97).
- Produces : « Manage Gallery » traduisible ; retirée de `interdites.txt`.

- [ ] **Step 1 : écrire `view/adminhtml/requirejs-config.js`**

```js
/**
 * maxcode/module-translation-fixes — MIT, Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 */
var config = {
    config: {
        mixins: {
            'Magento_MediaGalleryUi/js/grid/massaction/massactionView': {
                'Maxcode_TranslationFixes/js/media-gallery/massaction-view-mixin': true
            }
        }
    }
};
```

- [ ] **Step 2 : écrire `view/adminhtml/web/js/media-gallery/massaction-view-mixin.js`**

```js
/**
 * maxcode/module-translation-fixes — MIT, Copyright (c) 2026 eBusiness360 – Maxime LESGUILLIER.
 *
 * massactionView.js porte « Manage Gallery » et « Media Gallery » EN DUR, puis
 * cherche le titre de page par $('h1:contains(<ce texte anglais>)'). Le titre
 * etant traduit cote serveur, la comparaison echoue en francais et le mode
 * « Supprimer des images » vise un conteneur inexistant. On fait passer ces
 * deux valeurs par $t() : titre et comparaison utilisent le meme texte.
 */
define(['mage/translate'], function ($t) {
    'use strict';

    return function (MassactionView) {
        return MassactionView.extend({
            defaults: {
                standAloneTitle: $t('Manage Gallery'),
                slidePanelTitle: $t('Media Gallery')
            }
        });
    };
});
```

- [ ] **Step 3 : retirer « Manage Gallery » de la liste interdite du pack** — éditer `language-fr_fr/outils/interdites.txt` pour qu'il ne reste que :
```
# Chaines comparees ou affectees en dur dans du JavaScript.
# Ne les traduire qu'apres correction du code par maxcode/module-translation-fixes.
```

- [ ] **Step 4 : vérification dans le navigateur** — dans ttlx (traductions JS régénérées) :

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && ddev exec php bin/magento cache:clean translate config && ddev exec php bin/magento setup:static-content:deploy -f --area adminhtml fr_FR en_US 2>&1 | tail -1 && ddev exec php bin/magento cache:flush >/dev/null"`

Puis dans le navigateur intégré (compte de test) : Contenu → Galerie média ; vérifier par `javascript_tool` que `require('Magento_MediaGalleryUi/js/grid/massaction/massactionView')` a ses défauts passés par `$t` (exécuter `require('mage/translate')('Manage Gallery')` et comparer au titre `h1`) ; cliquer « Supprimer des images… » : les cases à cocher apparaissent sur les vignettes. Capture d'écran à l'appui.

- [ ] **Step 5 : CHANGELOG du compagnon** — ajouter sous `### Ajouté` : `- « Manage Gallery » et « Media Gallery » passés par \$t() dans la galerie média ; répare le mode « Supprimer des images » en langue traduite.`

- [ ] **Step 6 : commits et push des deux dépôts** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local/vendor/maxcode/module-translation-fixes && git add -A && git commit -q -m 'feat: galerie media, titres passes par \$t() (repare Supprimer des images)' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd /home/maxime/projects/language-fr_fr && git add outils/interdites.txt && git commit -q -m 'chore(outils): Manage Gallery n est plus interdite (corrigee par le compagnon)' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && echo ok"`

---

### Task 11 : Compatibilité — plugin sur une classe absente

**Files :** aucun livrable ; expérience à blanc dans ttlx, annulée à la fin.

- [ ] **Step 1 : déclarer un plugin sur une classe inexistante** — ajouter temporairement dans `ttlx-local/vendor/maxcode/module-translation-fixes/etc/adminhtml/di.xml`, avant `</config>` :
```xml
    <type name="Maxcode\ClasseInexistante\Pour\Test">
        <plugin name="maxcode_test_absente" type="Maxcode\TranslationFixes\Plugin\LoginAsCustomer\TraduireLibelleBouton"/>
    </type>
```

- [ ] **Step 2 : compiler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && ddev exec php bin/magento setup:di:compile 2>&1 | tail -3"`
Expected : `Generated code and dependency injection configuration successfully.` → un site qui a retiré `Magento_LoginAsCustomerAdminUi` n'est pas bloqué ; **noter le résultat dans le CHANGELOG du compagnon** (section « Compatibilité »). Si la compilation échoue : ajouter `magento/module-login-as-customer-admin-ui` et `magento/module-media-gallery-ui` (contrainte `*`) au `require` du `composer.json` du compagnon, et l'écrire dans son README (« exige les modules Login as Customer et Media Gallery du cœur »).

- [ ] **Step 3 : annuler** — retirer les lignes du Step 1, puis Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local/vendor/maxcode/module-translation-fixes && git status --short && cd /home/maxime/projects/ttlx-local && ddev exec php bin/magento setup:di:compile 2>&1 | tail -1"`
Expected : seul `CHANGELOG.md` apparaît modifié ; compilation réussie. Commit et push du CHANGELOG.

---

## Phase 1 — Reprise de l'existant

### Task 12 : Installer le pack dans ttlx et y reprendre l'existant

**Files :**
- Create : `language-fr_fr/outils/reprendre-existant.php` ; `language-fr_fr/core.csv`, `amasty.csv`, `mageplaza.csv` (et autres selon le classement)
- Delete (ttlx, branche `feature/pack-traduction-fr`) : `app/i18n/maxcode/fr_fr/`, `app/code/Maxcode/AmastyTranslation/`, `app/code/Maxcode/MageplazaTranslation/`, `app/code/Maxcode/CoreTranslation/`

**Interfaces :**
- Consumes : `outils/a-traduire/index.csv` (tâche 7), `Csv`, `Normaliseur`, `Classement`.
- Produces : premiers fichiers du pack, `verifier.php` vert, `tester-effet.php` vert dans ttlx.

- [ ] **Step 1 : écrire `outils/reprendre-existant.php`**

```php
<?php
declare(strict_types=1);

/**
 * Reprise unique de l'existant : pack Ambiance (751, sur-ensemble de ttlx),
 * AmastyTranslation, MageplazaTranslation et le CSV de CoreTranslation.
 * Chaque cle est rangee d'apres l'index d'extraction ; a defaut, d'apres sa
 * source. Les cles traduites par le communautaire sont ecartees.
 * Usage : outils/php outils/reprendre-existant.php <dossier-des-sources>
 * (le dossier contient : pack.csv, amasty.csv, mageplaza.csv, core.csv)
 */
require __DIR__ . '/autoload.php';

use Maxcode\LanguagePack\Outils\Csv;
use Maxcode\LanguagePack\Outils\Normaliseur;

$sources = rtrim($argv[1] ?? '', '/');
$racine = dirname(__DIR__);
$index = [];
$h = fopen(__DIR__ . '/a-traduire/index.csv', 'rb');
while (($l = fgetcsv($h, 0, ',', '"', '')) !== false) {
    if ($l !== [null] && count($l) >= 4) {
        $index[$l[0]] = $l[2];
    }
}
fclose($h);
$communautaire = [];
foreach (Csv::lire(__DIR__ . '/.cache/communautaire.csv') as $e) {
    $communautaire[$e['cle']] = $e['traduction'];
}

$parFichier = [];
$rangee = [];    // cle => editeur deja choisi : une cle, un fichier
$ecartees = [];
$inconnues = [];
foreach (['pack' => 'core', 'amasty' => 'amasty', 'mageplaza' => 'mageplaza', 'core' => 'core'] as $source => $defaut) {
    foreach (Csv::lire("$sources/$source.csv") as $e) {
        $cle = $e['cle'];
        if (isset($communautaire[$cle]) && $communautaire[$cle] !== $cle) {
            $ecartees[] = $cle;
            continue;
        }
        if (!isset($index[$cle]) && !isset($rangee[$cle])) {
            $inconnues[] = "$source\t$cle";
        }
        $editeur = $index[$cle] ?? $rangee[$cle] ?? $defaut;
        $rangee[$cle] = $editeur;
        $parFichier[$editeur][$cle] = Normaliseur::normaliser($e['traduction']);
    }
}
foreach ($parFichier as $editeur => $entrees) {
    Csv::ecrire("$racine/$editeur.csv", $entrees);
    printf("%-14s %5d entrée(s)\n", "$editeur.csv", count($entrees));
}
printf("Écartées (traduites par le communautaire) : %d\n", count($ecartees));
foreach ($ecartees as $c) {
    echo "  - $c\n";
}
file_put_contents(__DIR__ . '/a-traduire/reprise-inconnues.txt', implode("\n", $inconnues) . "\n");
printf("Clés absentes de l'index (rangées d'après leur source) : %d — outils/a-traduire/reprise-inconnues.txt\n", count($inconnues));
```

- [ ] **Step 2 : rassembler les sources et lancer la reprise**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && ls ../ttlx-local/app/code/Maxcode/*Translation/i18n/ && mkdir -p outils/.cache/reprise && cp ../ambiance-m2/app/i18n/maxcode/fr_fr/fr_FR.csv outils/.cache/reprise/pack.csv && cp ../ttlx-local/app/code/Maxcode/AmastyTranslation/i18n/fr_FR.csv outils/.cache/reprise/amasty.csv && cp ../ttlx-local/app/code/Maxcode/MageplazaTranslation/i18n/fr_FR.csv outils/.cache/reprise/mageplaza.csv && cp ../ttlx-local/app/code/Maxcode/CoreTranslation/i18n/fr_FR.csv outils/.cache/reprise/core.csv && outils/php outils/reprendre-existant.php outils/.cache/reprise"`
Expected : le `ls` montre un `fr_FR.csv` par module (si un module range ses traductions autrement, adapter le `cp` correspondant) ; un fichier par éditeur ; `View Transaction Details` dans la liste des écartées (elle est traduite par le communautaire) ; un nombre de clés hors index à examiner.

- [ ] **Step 3 : examiner les clés hors index** — lire `outils/a-traduire/reprise-inconnues.txt` : ce sont des chaînes de modules absents des trois sites (ex. `amasty/cart`, retiré de ttlx en juillet). Les garder (inoffensives) sauf si `grep -rF` dans les trois `vendor/` montre qu'elles appartiennent à un autre éditeur que celui choisi : alors déplacer la ligne dans le bon fichier.

- [ ] **Step 4 : ajouter les chaînes débloquées par le compagnon à `core.csv`** — ajouter (Edit) dans `core.csv` :
```
"Login as Customer","Connexion en tant que client"
"Manage Gallery","Gérer la galerie"
```
(« Login as Customer » est déjà présent via CoreTranslation : ne pas le doubler. Si `verifier.php` classe l'une de ces deux clés `communautaire`, retirer la ligne : la traduction du pack communautaire s'appliquera d'elle-même maintenant que le compagnon rend la chaîne traduisible.)

- [ ] **Step 5 : contrôler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/verifier.php | tail -15"`
Expected : `… : 0 erreur(s), N avertissement(s).` ; corriger toute erreur (en général : variables mal reportées, guillemets droits hors balisage) dans le fichier concerné, puis relancer jusqu'à 0 erreur.

- [ ] **Step 6 : commit et push** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add *.csv outils/reprendre-existant.php && git commit -q -m 'feat: reprise du pack existant, d AmastyTranslation, MageplazaTranslation et CoreTranslation' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && git log --oneline -1 && outils/php outils/fusionner.php"`
Expected : le hash du commit, puis le tableau des listes de travail **sans** les clés reprises (fusionner.php écarte ce qui est déjà dans le pack).

- [ ] **Step 7 : installer le pack dans ttlx EN RETIRANT la copie locale dans le même mouvement** (sinon `maxcode_fr_fr` serait enregistré deux fois)

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && git rm -r -q app/i18n/maxcode/fr_fr && ddev exec php bin/magento module:disable Maxcode_AmastyTranslation Maxcode_MageplazaTranslation Maxcode_CoreTranslation && git rm -r -q app/code/Maxcode/AmastyTranslation app/code/Maxcode/MageplazaTranslation app/code/Maxcode/CoreTranslation && ddev composer config repositories.maxcode-language-fr vcs git@github.com:eBusiness360/language-fr_fr.git && ddev composer config preferred-install.maxcode/language-fr_fr source && ddev composer require 'maxcode/language-fr_fr:dev-main' --no-interaction 2>&1 | grep -E 'maxcode|community|Lock file' && ddev exec php bin/magento setup:upgrade 2>&1 | tail -1 && ddev exec php bin/magento cache:flush >/dev/null && git status --short | head"`
Expected : `maxcode/language-fr_fr (dev-main)` installé, `community-engineering/language-fr_fr` monté en 0.0.64, `Upgrade completed successfully.`

- [ ] **Step 8 : test d'effet dans ttlx**

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php var/pack-fr/outils/tester-effet.php; echo code=\$?; rm -rf var/pack-fr"`
Expected : `maxcode_fr_fr : /var/www/html/vendor/maxcode/language-fr_fr`, une ligne `OK` par fichier et par aire, `Le pack est chargé et l'emporte.`, `code=0`.

- [ ] **Step 9 : vérifier le bouton dans l'admin** — fiche client de ttlx dans le navigateur intégré : le bouton affiche « Connexion en tant que client » (traduit par le pack, via le compagnon). Capture.

- [ ] **Step 10 : commit de la branche ttlx** (ni fusion ni push : la migration d'OVH TEST est la tâche 20)

Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && git add composer.json && { git ls-files --error-unmatch app/etc/config.php >/dev/null 2>&1 && git add app/etc/config.php || true; } && git commit -q -m 'build: traductions par les paquets maxcode/language-fr_fr et module-translation-fixes' -m 'Retire la copie locale du pack et les modules AmastyTranslation, MageplazaTranslation, CoreTranslation : leur contenu est dans le pack, le correctif DI de CoreTranslation dans le compagnon.' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git log --oneline -1"`

---

## Phase 2 — Vagues de traduction

Chaque vague suit le même cycle, outillé. **Règles de traduction** (spec § 5, rappel pour l'agent traducteur) : traduire la colonne 2 de `outils/a-traduire/<editeur>.csv` en s'appuyant sur la colonne 3 (module) pour le contexte ; vouvoiement ; vocabulaire du glossaire ; conserver `%1 %s {{…}}` et les balises ; laisser **vide** une ligne dont le sens est incertain et la consigner dans `outils/a-traduire/<editeur>-questions.txt` (une ligne : clé, module, question) pour l'utilisateur ; ne jamais copier une traduction d'un `fr_FR.csv` d'éditeur. Traiter par lots de 200 lignes, en éditant le fichier avec Edit/Write.

### Task 13 : Vague 1 — Amasty, Mageplaza

- [ ] **Step 1 : traduire** `outils/a-traduire/amasty.csv` puis `outils/a-traduire/mageplaza.csv` (lots de 200, règles ci-dessus).
- [ ] **Step 2 : intégrer et contrôler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/integrer.php amasty && outils/php outils/integrer.php mageplaza && outils/php outils/verifier.php | tail -5"`. Expected : `0 erreur(s)` ; sinon corriger et relancer.
- [ ] **Step 3 : effet dans ttlx** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add amasty.csv mageplaza.csv outils/zones && git commit -q -m 'feat(amasty, mageplaza): vague 1' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd ../ttlx-local/vendor/maxcode/language-fr_fr && git pull -q && cd ../../.. && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php bin/magento cache:clean translate >/dev/null && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.`
- [ ] **Step 4 : fiches de relecture** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/echantillon.php amasty && outils/php outils/echantillon.php mageplaza"` ; envoyer les deux fiches à l'utilisateur (SendUserFile) avec les éventuels `*-questions.txt`.
- [ ] **Step 5 : appliquer ses remarques** — corriger les lignes signalées ; si une erreur est récurrente (terme, registre), la corriger dans **tout** le fichier de l'éditeur ; relancer Step 2 ; commit `fix(amasty, mageplaza): relecture vague 1` et push.

### Task 14 : Vague 2 — Cœur Magento

- [ ] **Step 1 : traduire** `outils/a-traduire/core.csv` (lots de 200 ; attention aux chaînes de `lib/web` et des modules d'administration).
- [ ] **Step 2 : intégrer et contrôler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/integrer.php core && outils/php outils/verifier.php | tail -5"`. Expected : `0 erreur(s)`.
- [ ] **Step 3 : effet dans ttlx** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add core.csv outils/zones && git commit -q -m 'feat(core): vague 2' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd ../ttlx-local/vendor/maxcode/language-fr_fr && git pull -q && cd ../../.. && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php bin/magento cache:clean translate >/dev/null && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.`
- [ ] **Step 4 : fiche de relecture** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/echantillon.php core"` ; envoyer à l'utilisateur.
- [ ] **Step 5 : appliquer ses remarques** — corrections (récurrentes : dans tout `core.csv`), Step 2 de nouveau, commit `fix(core): relecture vague 2`, push.

### Task 15 : Vague 3 — MageWorx, Mirasvit, Magefan

- [ ] **Step 1 : traduire** `outils/a-traduire/mageworx.csv`, `mirasvit.csv`, `magefan.csv` (lots de 200).
- [ ] **Step 2 : intégrer et contrôler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in mageworx mirasvit magefan; do outils/php outils/integrer.php \$e; done && outils/php outils/verifier.php | tail -5"`. Expected : `0 erreur(s)`.
- [ ] **Step 3 : effet** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add mageworx.csv mirasvit.csv magefan.csv outils/zones && git commit -q -m 'feat(mageworx, mirasvit, magefan): vague 3' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd ../ttlx-local/vendor/maxcode/language-fr_fr && git pull -q && cd ../../.. && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php bin/magento cache:clean translate >/dev/null && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.`
- [ ] **Step 4 : fiches** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in mageworx mirasvit magefan; do outils/php outils/echantillon.php \$e; done"` ; envoyer à l'utilisateur.
- [ ] **Step 5 : remarques** — corrections, Step 2, commit `fix(mageworx, mirasvit, magefan): relecture vague 3`, push.

### Task 16 : Vague 4 — Smile, Fooman, BSS, Xtento et les autres éditeurs techniques

Éditeurs : tous les fichiers de `outils/a-traduire/` qui ne relèvent ni des vagues 1, 2, 3 ni de la vague 5 (`magezon`, `smartwave`, `swissup`) — notamment `smile`, `fooman`, `bss`, `xtento`, `webkul`, `payplug`, `nukium`, `fintecture`, `mageprince`, `magepal`, `yireo`, `blackbird`, `paypal`, `avstudnitz`.

- [ ] **Step 1 : lister les éditeurs de la vague** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr/outils/a-traduire && ls *.csv | sed 's/.csv//' | grep -vxE 'index|amasty|mageplaza|core|mageworx|mirasvit|magefan|magezon|smartwave|swissup' | tr '\n' ' '"` ; noter la liste.
- [ ] **Step 2 : traduire** chaque fichier de la liste (lots de 200).
- [ ] **Step 3 : intégrer et contrôler** — Run (remplacer la liste par celle du Step 1) : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in smile fooman bss xtento webkul payplug nukium fintecture mageprince magepal yireo blackbird paypal avstudnitz; do [ -f outils/a-traduire/\$e.csv ] && outils/php outils/integrer.php \$e; done; outils/php outils/verifier.php | tail -5"`. Expected : `0 erreur(s)`.
- [ ] **Step 4 : effet** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add *.csv outils/zones && git commit -q -m 'feat: vague 4, editeurs techniques' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd ../ttlx-local/vendor/maxcode/language-fr_fr && git pull -q && cd ../../.. && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php bin/magento cache:clean translate >/dev/null && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.`
- [ ] **Step 5 : fiches** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in smile fooman bss xtento webkul payplug nukium fintecture mageprince magepal yireo blackbird paypal avstudnitz; do [ -f \$e.csv ] && outils/php outils/echantillon.php \$e; done"` ; envoyer à l'utilisateur.
- [ ] **Step 6 : remarques** — corrections, Step 3, commit `fix: relecture vague 4`, push.

### Task 17 : Vague 5 — Magezon, Porto (Smartwave), Breeze (Swissup)

- [ ] **Step 1 : traduire** `outils/a-traduire/magezon.csv`, `smartwave.csv`, `swissup.csv` (lots de 200 ; ce sont surtout des chaînes vues par le client : soigner le registre).
- [ ] **Step 2 : intégrer et contrôler** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in magezon smartwave swissup; do outils/php outils/integrer.php \$e; done && outils/php outils/verifier.php | tail -5"`. Expected : `0 erreur(s)`.
- [ ] **Step 3 : effet** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add magezon.csv smartwave.csv swissup.csv outils/zones && git commit -q -m 'feat(magezon, smartwave, swissup): vague 5' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git push -q && cd ../ttlx-local/vendor/maxcode/language-fr_fr && git pull -q && cd ../../.. && rm -rf var/pack-fr && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php bin/magento cache:clean translate >/dev/null && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.`
- [ ] **Step 4 : contrôle visuel** — sur Ambiance local (Porto + Magezon, `https://ambiance-m2.ddev.site`) et ttlx (Breeze), après installation temporaire du pack en local uniquement (ne rien commiter dans ces dépôts) : page d'accueil, fiche produit, panier ; aucune chaîne tronquée ni débordante. Captures.
- [ ] **Step 5 : fiches** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for e in magezon smartwave swissup; do outils/php outils/echantillon.php \$e; done"` ; envoyer à l'utilisateur.
- [ ] **Step 6 : remarques** — corrections, Step 2, commit `fix(magezon, smartwave, swissup): relecture vague 5`, push.

---

## Phase 3 — Publication et migrations

### Task 18 : Publication 2.0.0 / 1.0.0

- [ ] **Step 1 : contrôle d'acceptation** (spec § 12) — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && outils/php outils/telecharger-communautaire.php && outils/php outils/verifier.php | tail -2 && outils/phpunit | tail -1"`. Expected : `0 erreur(s)`, `OK (27 tests, …)`. CI verte sur le dernier commit.
- [ ] **Step 2 : CHANGELOG** — dans le pack, remplacer `## [Non publié]` par `## [2.0.0] — <date du jour>` et lister, sous `### Ajouté`, un fichier par éditeur avec son nombre d'entrées (Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && for f in *.csv; do printf '%s %s\n' \$f \$(grep -c '' \$f); done"`) ; dans le compagnon, `## [1.0.0] — <date du jour>`.
- [ ] **Step 3 : tags** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/language-fr_fr && git add CHANGELOG.md && git commit -q -m 'release: 2.0.0' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git tag -a 2.0.0 -m 2.0.0 && git push -q && git push -q origin 2.0.0 && cd /home/maxime/projects/ttlx-local/vendor/maxcode/module-translation-fixes && git add CHANGELOG.md && git commit -q -m 'release: 1.0.0' -m 'Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>' && git tag -a 1.0.0 -m 1.0.0 && git push -q && git push -q origin 1.0.0 && echo ok"`
- [ ] **Step 4 : enregistrer sur Packagist (utilisateur)** — les dépôts étant publics, l’utilisateur les enregistre sur **packagist.org** (Submit : URL GitHub de chaque dépôt) avec son compte. Vérifier : Run : `curl -s -o /dev/null -w '%{http_code}\n' https://repo.packagist.org/p2/maxcode/language-fr_fr.json` → `200` (idem `maxcode/module-translation-fixes`).

### Task 19 : Migration d'Ambiance

- [ ] **Step 1 : config.php est-il versionné ?** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ambiance-m2 && git pull -q && git ls-files --error-unmatch app/etc/config.php >/dev/null 2>&1 && echo versionne || echo non-versionne"`.
- [ ] **Step 2 : en local** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ambiance-m2 && git checkout -q -b feature/pack-traduction-fr && cp -p composer.lock /tmp/ambiance-lock-avant && ddev exec php bin/magento module:disable Maxcode_CoreTranslation && git rm -r -q app/i18n/maxcode/fr_fr app/code/Maxcode/CoreTranslation && ddev composer require 'maxcode/language-fr_fr:^2.0' 'maxcode/module-translation-fixes:^1.0' 'community-engineering/language-fr_fr:^0.0.64' --no-interaction 2>&1 | grep -E 'maxcode|community|Lock file' && ddev exec php bin/magento setup:upgrade 2>&1 | tail -1 && ddev exec php bin/magento setup:di:compile 2>&1 | tail -1 && ddev exec php bin/magento cache:flush >/dev/null"`. Expected : deux paquets installés (et le communautaire monté en 0.0.64) ; upgrade et compilation réussis.
- [ ] **Step 3 : test d'effet + verrou** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ambiance-m2 && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"` → `Le pack est chargé et l'emporte.` Puis comparer `/tmp/ambiance-lock-avant` et `composer.lock` (script Python de la bascule de l'habillage : seuls `maxcode/language-fr_fr`, `maxcode/module-translation-fixes` ajoutés et `community-engineering/language-fr_fr` modifié).
- [ ] **Step 4 : commit** — si `config.php` est versionné, l'inclure (module désactivé) ; sinon noter pour le serveur. Commit `build: traductions par les paquets maxcode (retire la copie locale et CoreTranslation)`, push de la branche, essai à blanc serveur du `composer install` (méthode de la bascule de l'habillage : copies `composer.essai.*`), fusion dans `master`, push.
- [ ] **Step 5 : si `config.php` n'est PAS versionné** — sur le serveur, AVANT le déploiement : Run : `ssh -o BatchMode=yes web01 "su - ambiancelounge -s /bin/bash -c 'cd /home/ambiancelounge.fr/public_html && php bin/magento module:disable Maxcode_CoreTranslation'"`.
- [ ] **Step 6 : l'utilisateur lance son `simpledeploy.sh`** ; puis vérifier : 4 domaines en 200, admin `/admin44980` en 200, `tester-effet.php` sur le serveur (copie dans `var/pack-fr`, même méthode qu'au Step 3 via `su - ambiancelounge`), 0 rapport dans `var/report` sur 30 minutes, contrôle à +2 minutes.

### Task 20 : Migration de ttlx / OVH TEST

- [ ] **Step 1 : passer des versions de travail aux versions publiées** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/ttlx-local && git checkout -q feature/pack-traduction-fr && ddev composer config --unset repositories.maxcode-language-fr && ddev composer config --unset repositories.maxcode-translation-fixes && ddev composer config --unset preferred-install.maxcode/language-fr_fr && ddev composer config --unset preferred-install.maxcode/module-translation-fixes && ddev composer require 'maxcode/language-fr_fr:^2.0' 'maxcode/module-translation-fixes:^1.0' --no-interaction 2>&1 | grep -E 'maxcode|Lock file'"`. Expected : installation depuis Packagist (archive), versions 2.0.0 et 1.0.0.
- [ ] **Step 2 : test d'effet local** — même commande que la tâche 12, Step 8. Expected : `code=0`.
- [ ] **Step 3 : commit, fusion, push** — commit `build: paquets de traduction en versions publiees`, `git checkout main && git merge --ff-only feature/pack-traduction-fr && git push -q origin main`.
- [ ] **Step 4 : déploiement OVH TEST** — le nouveau `simpledeploy.sh` s'arrête sur le garde-fou « absents du composer.lock » et affiche la commande. Avant de le lancer, désactiver les trois modules retirés : Run : `ssh -o BatchMode=yes web01 "su - ebusines -s /bin/bash -c 'cd /home/ebusiness-atlantique.fr/domains/ttlx.ebusiness-atlantique.fr/public_html && php bin/magento module:disable Maxcode_AmastyTranslation Maxcode_MageplazaTranslation Maxcode_CoreTranslation'"` ; puis git pull + `composer update maxcode/language-fr_fr maxcode/module-translation-fixes community-engineering/language-fr_fr --no-interaction` (une fois, comme indiqué par le garde-fou) ; puis `simpledeploy.sh`. Vérifications : `tester-effet.php` sur le serveur, front et admin 200, contrôle à +2 minutes.

### Task 21 : Migration de mojo

- [ ] **Step 1 : en local (DDEV mojo)** — Run : `MSYS_NO_PATHCONV=1 wsl.exe -d Ubuntu -e bash -c "cd /home/maxime/projects/mojo-m2 && ddev composer require 'maxcode/language-fr_fr:^2.0' 'maxcode/module-translation-fixes:^1.0' 'community-engineering/language-fr_fr:^0.0.64' --no-interaction 2>&1 | grep -E 'maxcode|community|Lock file' && ddev exec php bin/magento setup:upgrade 2>&1 | tail -1 && mkdir -p var/pack-fr && cp -r ../language-fr_fr/outils var/pack-fr/outils && ddev exec php var/pack-fr/outils/tester-effet.php | tail -3; rm -rf var/pack-fr"`. Expected : `Le pack est chargé et l'emporte.` (mojo n'a rien à retirer ; `composer.json` n'y est pas versionné : rien à commiter.)
- [ ] **Step 2 : en production** — script dans le modèle de `bascule-mojo.sh` : phase 0 (essai à blanc `COMPOSER=composer.bascule.json … require … --dry-run` → exactement les paquets attendus), puis maintenance, cron coupé, `composer require` des deux paquets, `setup:upgrade`, `di:compile`, `cache:clean translate config`, statiques `Smartwave/porto_child` `fr_FR en_US` + adminhtml, opcache des deux domaines, réouverture, contrôles HTTP et après le premier cron, `tester-effet.php`.

---

## Auto-revue (faite à l'écriture)

- **Couverture de la spec :** § 4 → T1, T9 ; § 5 → T3–T6 (règles), T12 (reprise) ; § 6 → T2–T8 ; § 7 → T9–T11 ; § 8 → T13–T18 ; § 9 → T19–T21 ; § 12 → T18 Step 1. Ajouts nécessaires repérés : enregistrement Packagist (T18 Step 4, exigé par « simple `composer require` » de la spec § 10).
- **Spec alignée sur le plan (01/10/2026) :** noms de fichiers d'éditeur = préfixe du module (`bss.csv`) ; « majuscules » et « vouvoiement » passés en avertissements ; `verifier.php` compare au communautaire de référence téléchargé (CI sans Magento) ; extraction par appel direct du collecteur ; candidates JS = lignes qui comparent un texte ; enregistrement Packagist dans la publication.
- **Fait vérifié à l'écriture :** le parseur i18n de Magento cumule ses phrases d'un appel à l'autre (`AbstractParser::$_phrases` jamais vidé, générateur en cache statique dans `ServiceLocator`) — d'où la remise à zéro par réflexion dans `extraire.php`. `Magento\Setup\` est bien dans l'autoload PSR-4 du `composer.json` de ttlx.
- **Cohérence des noms :** `Csv::lire/ecrire`, `Typographie::erreurs/avertissements/texteVisible/NBSP`, `Variables::extraire`, `Normaliseur::normaliser`, `Constat`, `Verificateur::verifierPack`, `Classement::editeur/exclu`, `Integration::integrer` — identiques dans toutes les tâches.
