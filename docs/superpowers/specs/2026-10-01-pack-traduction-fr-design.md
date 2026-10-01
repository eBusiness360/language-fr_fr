# Pack de traduction français gratuit — complément au pack communautaire

**Date :** 2026-10-01
**Statut :** conception validée, plan d'implémentation à écrire
**Livrables :** deux dépôts publics `github.com/eBusiness360`, licence MIT
**Sites de travail :** `ttlx-local`, `ambiance-m2`, `mojo-m2` (Magento 2.4.8-p5, DDEV)

---

## 1. Objectif

Publier gratuitement un pack de langue français pour Magento 2 qui traduit **ce que le pack
communautaire ne traduit pas** : le reste du cœur Magento et les modules tiers courants
(Amasty, Mageplaza, Mirasvit, MageWorx, Magefan, Smile, Fooman, BSS, Xtento, Magezon,
Porto, Swissup Breeze…). Il s'installe **par-dessus** `community-engineering/language-fr_fr`,
qu'il ne recopie jamais.

Un second paquet gratuit et facultatif corrige le code de Magento là où une chaîne échappe
à toute traduction par CSV.

**Critère de réussite :** sur un Magento 2.4.x équipé des deux packs, aucune chaîne connue
du cœur ou des modules couverts ne reste en anglais, et rien de ce que traduit le pack
communautaire n'est modifié.

## 2. État des lieux (relevé le 2026-10-01)

### Le pack existant

`maxcode/language-fr_fr` (type `magento2-language`, version 1.0.0, licence propriétaire)
existe en copie locale `app/i18n/maxcode/fr_fr/` sur ttlx et Ambiance. Il est déjà conçu
comme un complément :

- `language.xml` : `sort_order 100`, `use vendor="community-engineering" package="fr_fr"` ;
- règles d'écriture apprises le 29/09/2026, documentées dans `language.xml` (apostrophe
  typographique obligatoire, « Manage Gallery » à ne pas traduire).

Les deux copies **ont divergé sans se contredire** : ttlx 717 entrées, Ambiance 751 (les 34 de
plus concernent le panier de devis Amasty), aucune clé traduite différemment.

Recoupement avec le pack communautaire : **750 entrées sur 751 sont des ajouts**. Une seule
reprend une chaîne communautaire — « View Transaction Details » (préférence de formulation, à
retirer, cf. § 5).

### Les modules de traduction

| Module | Sites | Contenu |
|---|---|---|
| `Maxcode_AmastyTranslation` | ttlx | `i18n/fr_FR.csv`, 1 208 lignes |
| `Maxcode_MageplazaTranslation` | ttlx | 105 lignes (CustomerApproval, QuickFlushCache) |
| `Maxcode_CoreTranslation` | ttlx, Ambiance | 19 chaînes du back-office + `etc/adminhtml/di.xml` qui écrit « Connexion en tant que client » **en dur** pour deux `virtualType` de `Magento_LoginAsCustomerAdminUi` (le `translatable="true"` y est ignoré) |

### Le pack communautaire

`community-engineering/language-fr_fr` 0.0.61, installé sur les trois sites : 11 142 lignes,
10 965 traductions, `language.xml` sans `sort_order` (donc 0). Sa typographie est
**incohérente** : 3 744 apostrophes droites contre 681 typographiques ; avant « : », 417
espaces normales contre 131 insécables. On ne s'aligne sur lui que pour le **vocabulaire**.

### Mécanique de chargement (vérifiée)

- Ordre : module → pack de langue → thème → base ; le dernier chargé l'emporte. Entre packs,
  arbitrage sur `sort_order`.
- `Magento\Framework\App\Language\Dictionary::readPackCsv()` (l. 234-246) charge **tous les
  `*.csv` de la racine du pack, par ordre alphabétique**. Un fichier par éditeur est donc
  possible, et un doublon de clé entre fichiers serait arbitré en silence par l'alphabet.

### Volume à traduire

Chaînes des `i18n/en_US.csv` des modules tiers présents sur les trois sites, moins ce que
traduisent déjà le pack communautaire et les traductions Maxcode :

| Éditeur | Chaînes | Déjà traduites | À traduire |
|---|---|---|---|
| MageWorx | 1 703 | 195 | 1 508 |
| Amasty | 2 408 | 1 388 | 1 020 |
| Magezon | 1 066 | 135 | 931 |
| Mirasvit | 1 120 | 222 | 898 |
| Smile | 656 | 98 | 558 |
| Mageplaza | 829 | 272 | 557 |
| Xtento | 504 | 67 | 437 |
| Webkul, PayPlug, Magefan, Fooman, Nukium, Fintecture, MagePrince, BSS, Smartwave, MagePal, Swissup, avstudnitz | 1 517 | 301 | 1 216 |
| **Total dédoublonné** | **8 884** | **1 934** | **6 950** |

Certaines chaînes sont communes à plusieurs éditeurs : le total dédoublonné est donc inférieur
à la somme des lignes.

C'est un **minimum** : les modules qui déclarent leurs chaînes dans le code plutôt que dans
un `en_US.csv` (Swissup Breeze notamment) en ajouteront. Estimation finale : 8 000 à 10 000.

### Outils existants

À la racine de ttlx : `verifier_paquet_langue.php` (format CSV, doublons, traductions
identiques à la source) et `tester_paquet_langue.php` (preuve que le pack l'emporte
réellement dans Magento). Ils servent de base aux outils du § 6.

## 3. Décisions

| Sujet | Décision |
|---|---|
| Rapport au pack communautaire | complément **par-dessus**, jamais de copie de ses traductions |
| Périmètre v1 | le cœur + **tous** les éditeurs dont on peut extraire les chaînes ; les chaînes de modules absents d'un site sont sans effet |
| Traductions des éditeurs | jamais reprises (produits sous licence) ; leurs chaînes anglaises servent de clés |
| Licence | **MIT**, © eBusiness360 |
| Chaînes intraduisibles par CSV | **module compagnon gratuit**, sans texte français |
| Organisation | **un pack, un fichier CSV par source** (approche A) |
| Qualité | contrôles automatiques bloquants + échantillon relu par l'utilisateur à chaque vague |
| Publication | dépôt privé pendant le travail, **public au tag 2.0.0** une fois toutes les vagues faites |

## 4. Dépôts, paquets, structure

| Dépôt | Paquet Composer | Rôle |
|---|---|---|
| `eBusiness360/language-fr_fr` | `maxcode/language-fr_fr`, type `magento2-language` | traductions — uniquement des CSV |
| `eBusiness360/module-translation-fixes` | `maxcode/module-translation-fixes` → `Maxcode_TranslationFixes` | correctifs de code, aucun texte français |

Le pack garde son nom et son `language.xml` actuels (`vendor maxcode`, `package fr_fr`,
`sort_order 100`, `use community-engineering/fr_fr`).

```
language-fr_fr/
├── core.csv            cœur Magento : ce que le communautaire ne traduit pas
├── amasty.csv  bss.csv  fooman.csv  magefan.csv  mageplaza.csv
├── mageworx.csv  magezon.csv  mirasvit.csv  smartwave.csv  smile.csv
├── swissup.csv  xtento.csv  …    un fichier par éditeur, à la racine
│                     (nom = préfixe du module en minuscules : Bss_* → bss.csv)
├── language.xml  registration.php  composer.json
├── LICENSE  README.md  CHANGELOG.md
├── outils/             extraction, contrôles, échantillons
└── .github/workflows/  intégration continue
```

`composer.json` du pack :

- `"license": "MIT"` ;
- **`require`** `community-engineering/language-fr_fr` (aujourd'hui seulement suggéré : un
  complément sans sa base n'a pas de sens) et `magento/framework ^103.0` (ligne 2.4, à partir
  de 2.4.4) ;
- `suggest` `maxcode/module-translation-fixes`.

`.gitattributes` : `outils/`, `.github/` et `docs/` en `export-ignore` — un site qui installe
le pack ne reçoit que les CSV et les fichiers Magento.

**Versions :** publication en **2.0.0** (les copies privées des sites portent 1.0.0 ; nouvelle
organisation des fichiers et nouvelle licence = rupture). Module compagnon en 1.0.0.
Ensuite : mineure = nouvelles chaînes ou nouvel éditeur, corrective = corrections.

## 5. Règles de traduction

### Ce qui entre dans le pack

Une chaîne entre si :

1. elle provient du cœur Magento ou d'un module tiers disponible ;
2. le pack communautaire **ne la traduit pas** : clé absente, ou traduction identique à
   l'anglais.

N'entrent jamais : une chaîne que le communautaire traduit déjà (même si on la formulerait
autrement — « View Transaction Details » est donc retirée) ; une traduction reprise d'un
`fr_FR.csv` d'éditeur ; les chaînes des modules Maxcode, qui embarquent leur propre `i18n`.

**Un seul fichier par chaîne (bloquant).** Une chaîne utilisée par le cœur et par un module
tiers va dans `core.csv` ; le fichier de l'éditeur ne la répète pas.

### Format

Deux colonnes, échappement CSV standard de Magento (guillemets doublés), UTF-8 sans BOM, fin de
ligne Unix, une ligne par entrée. Les clés sont recopiées **à l'octet près** depuis la source
(y compris les échappements comme `Active filters\\:`).

### Typographie (bloquant)

| Règle | Raison |
|---|---|
| apostrophe **’** (U+2019), jamais **'** | une apostrophe droite ferme la chaîne JavaScript d'un `data-bind` : Knockout tombe (galerie, 29/09/2026) |
| espace **insécable** (U+00A0) avant `: ; ! ?` et à l'intérieur de `« »` | typographie française, pas de ponctuation orpheline en début de ligne ; les 74 espaces normales du pack actuel sont converties |
| `« »` pour le texte, `"` seulement dans le balisage HTML | `<a href="…">` doit rester valide |
| **…** (U+2026) plutôt que `...` | cohérence |
| `%1`, `%2`, `%s`, `%d`, `{{…}}` et balises HTML conservés **en même nombre** | une variable perdue affiche un trou ou casse le rendu |

Une traduction identique à l'anglais est refusée, sauf liste blanche versionnée de noms propres
et de sigles (`outils/identiques-autorises.txt` : PayPal, SKU, URL, …).

### Vocabulaire et style (avertissement)

`outils/glossaire.csv` : termes les plus fréquents du pack communautaire (Cart → Panier,
Order → Commande, Store View → Vue magasin, …) complétés de nos choix. Un écart produit un
avertissement, pas un blocage.

Deux règles de style sont aussi des **avertissements**, faute de pouvoir être vérifiées
mécaniquement sans faux positifs (noms propres, marques) : majuscule en début de phrase
seulement (« Add To Cart » → « Ajouter au panier ») et vouvoiement côté client (le tutoiement
est signalé). La relecture par échantillon les tranche.

### Chaînes interdites (bloquant)

`outils/interdites.txt` : chaînes comparées ou affectées en dur dans du JavaScript. Elles ne
sont traduites qu'une fois que le module compagnon a corrigé le code concerné (« Manage
Gallery » en sort avec le correctif du § 7).

## 6. Outillage

Scripts PHP en ligne de commande dans `outils/`.

**`extraire.php <racine Magento>`** — liste de travail.
Récolte les `i18n/en_US.csv` des modules et passe chaque module, thème et `lib/web` au
collecteur de `bin/magento i18n:collect-phrases`, appelé directement (même détecteur que
Magento). Classe chaque chaîne par origine (`magento/*` → `core`, sinon l'éditeur) et par zone
(front si elle apparaît sous `view/frontend` ou dans un thème front, admin sinon). Retire ce que
traduisent le pack communautaire et le nôtre. Signale les candidates à la liste interdite
(chaîne présente dans une ligne `.js` qui **compare** un texte — `:contains(`, `==`, `!=` —
hors `$t(`), triées à la main. Écrit `outils/a-traduire/<éditeur>.csv` (chaîne, traduction
vide, module, zone) — **généré, non versionné**. Lancé sur ttlx, Ambiance et mojo, puis
fusionné (`fusionner.php`).

**`verifier.php`** — toutes les règles du § 5. Liste chaque ligne fautive (fichier, numéro,
règle) et sort en erreur s'il y a au moins une erreur. Compare au pack communautaire de
référence (version figée dans `outils/communautaire.version`, téléchargée depuis sa source
publique), ce qui permet de l'exécuter en CI sans Magento ; `tester-effet.php` contrôle en plus
la version réellement installée sur chaque site.

**`tester-effet.php <racine Magento>`** — preuve dans un vrai Magento : charge le dictionnaire
fusionné `fr_FR` et vérifie, pour **chaque fichier** du pack, qu'une chaîne témoin ressort avec
notre traduction, et que des chaînes du communautaire ressortent **inchangées**.

**`echantillon.php <éditeur>`** — fiche de relecture : toutes les chaînes du parcours client
d'abord, puis 30 chaînes d'admin tirées au hasard, anglais et français en regard.

**Intégration continue** (`.github/workflows/`, GitHub Actions) : à chaque push et proposition,
`composer install` puis `verifier.php`.

## 7. Module compagnon `Maxcode_TranslationFixes`

**Principe :** aucune traduction. Chaque correctif fait passer par `__()` ou `$t()` une chaîne
qui y échappait ; la traduction vit dans le pack — ou dans n'importe quel autre pack de langue.

### Correctif 1 — bouton « Login as Customer »

Fiche client et barre d'outils de la commande. Le libellé déclaré dans le `di.xml` de
`Magento_LoginAsCustomerAdminUi` arrive au gabarit en chaîne brute. Plugin `after` sur le
fournisseur de données des boutons (`Magento\LoginAsCustomerAdminUi\Ui\Customer\Component\Button\DataProvider`,
méthode exacte à confirmer à la réalisation) qui fait passer le libellé par `__()`.
« Login as Customer » rejoint `core.csv`.

### Correctif 2 — galerie média du back-office

`Magento_MediaGalleryUi/js/grid/massaction/massactionView.js` porte `standAloneTitle:
'Manage Gallery'` et `slidePanelTitle: 'Media Gallery'` en dur, puis cherche
`h1:contains(...)` avec ce texte anglais. Mixin RequireJS qui remplace ces valeurs par
`$t(...)` : titre et comparaison utilisent le même texte traduit. « Manage Gallery » sort de la
liste interdite ; la branche « Media Gallery », déjà traduite par le communautaire et donc
cassée en français, est réparée.

### Tests

Test unitaire du plugin (le libellé ressort en `Phrase`). Dans un DDEV : bouton en français ;
mode « Supprimer des images » de la galerie fonctionnel avec l'interface en français.

### Compatibilité

Certains sites retirent des modules du cœur par `replace`. À vérifier à la réalisation : un
plugin visant une classe absente ne doit pas faire échouer `setup:di:compile`. Sinon, chaque
correctif est isolé avec une dépendance déclarée sur son module cible. Un mixin sans cible est
ignoré par RequireJS.

## 8. Déroulé

| Vague | Contenu |
|---|---|
| 0. Socle | dépôts (privés), outils, CI ; reprise de l'existant — pack actuel (751), `AmastyTranslation` (1 208), `MageplazaTranslation` (105), CSV de `CoreTranslation` (19) — réparti par fichier et mis aux règles du § 5 ; module compagnon et ses deux correctifs |
| 1. Amasty + Mageplaza | compléter (~1 600) |
| 2. Cœur | ce que le communautaire ne traduit pas dans `magento/*` (volume mesuré par l'extraction) |
| 3. MageWorx, Mirasvit, Magefan | ~2 600 |
| 4. Smile, Fooman, BSS, Xtento et les autres | ~2 200 |
| 5. Magezon, Porto, Breeze | ~1 000 et plus |

**Cycle d'une vague :** extraction sur les trois sites → traduction module par module, en
s'appuyant sur le fichier source de chaque chaîne pour lever les ambiguïtés → `verifier.php` vert
→ `tester-effet.php` vert dans le DDEV → fiche `echantillon.php` relue par l'utilisateur → toute
erreur récurrente corrigée dans tout le fichier de l'éditeur, pas seulement dans l'échantillon →
commit.

**Publication :** tag **2.0.0** du pack et **1.0.0** du compagnon après la vague 5 ; passage des
deux dépôts en public et **enregistrement des deux paquets sur Packagist** (par l'utilisateur,
avec son compte) — c'est ce qui rend possible le simple `composer require` du § 10. Le README
expose les règles aux contributeurs ; la CI les applique.

## 9. Migration des sites

| Site | Retiré | Chemin |
|---|---|---|
| Ambiance | `app/i18n/maxcode/fr_fr`, `Maxcode_CoreTranslation` | `composer require` en local, commit unique (ajout des paquets + retraits), simpledeploy de l'utilisateur ; verrou versionné |
| ttlx / OVH TEST | `app/i18n/maxcode/fr_fr`, `Maxcode_AmastyTranslation`, `Maxcode_MageplazaTranslation`, `Maxcode_CoreTranslation` | commit unique ; le simpledeploy s'arrête sur le garde-fou « paquet absent du lock » et indique la mise à jour restreinte à lancer une fois |
| mojo | rien | configuration Composer sur le serveur (JSON non versionné), comme la bascule de l'habillage |

**Le piège :** le paquet de `vendor/` porte le même nom que la copie locale (`maxcode/fr_fr`).
Leur coexistence, même brève, enregistre le pack deux fois et fait tomber le site. Retrait de la
copie et arrivée du paquet partent **dans le même déploiement**.

**Retrait des modules :** `module:disable`, suppression du code, `setup:upgrade`, cron coupé
pendant la fenêtre (leçon de mojo du 29/09/2026 : le cron avait vidé `generated/` juste après un
retrait de module).

**Effet voulu, à surveiller :** les traductions d'Amasty passent d'un module à un pack de
`sort_order 100` ; elles l'emportent désormais aussi sur les `fr_FR.csv` livrés par l'éditeur.
Des libellés peuvent changer à l'écran.

**Vérification de chaque site :** `tester-effet.php`, front et admin en 200, traductions
JavaScript régénérées (`cache:clean translate` avant les statiques, déjà dans les trois
simpledeploy), contrôle deux minutes après la réouverture.

Les deux paquets étant publics, aucune *deploy key*.

## 10. Hors périmètre

- Les autres langues (le compagnon y est prêt, le pack non).
- Les autres sites de la flotte (Colony, actionpeche, …) : adoption ultérieure par simple
  `composer require`.
- La traduction du contenu (pages CMS, produits, e-mails personnalisés en base).
- Les chaînes de modules qu'aucun des trois sites ne possède.

## 11. Risques

| Risque | Parade |
|---|---|
| Chaînes ambiguës hors contexte (« Order », « Store ») | traduction module par module avec le fichier source ; échantillon relu |
| Chaîne comparée en dur dans du JavaScript, traduite par erreur | détection à l'extraction + liste interdite bloquante |
| Collision de clés entre éditeurs | un seul fichier par chaîne, bloquant |
| Libellés Amasty modifiés à l'écran après migration | vérification visuelle des écrans Amasty au moment de la migration |
| Plugin du compagnon sur une classe retirée par `replace` | test sur un Magento privé du module ciblé (§ 7) |
| Double enregistrement du pack pendant la migration | retrait et ajout dans le même déploiement (§ 9) |

## 12. Critères d'acceptation de la 2.0.0

- `verifier.php` sans erreur sur tous les fichiers ; CI verte.
- `tester-effet.php` vert sur ttlx, Ambiance et mojo.
- Fiches d'échantillon de chaque éditeur relues et corrections reportées.
- Aucune clé du pack présente et traduite dans le pack communautaire installé.
- Correctifs du compagnon vérifiés : bouton « Login as Customer » et suppression d'images de la
  galerie en français.
- Les trois sites migrés, front et admin en 200, aucune copie locale ni module de traduction
  résiduel.
