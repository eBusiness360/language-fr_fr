# Changelog — maxcode/language-fr_fr

Format : [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions : [SemVer](https://semver.org/lang/fr/).

## [2.0.0] — 2026-10-01

Première version publique. Elle remplace les paquets maison précédents (pack local
`app/i18n/maxcode/fr_fr`, modules `Maxcode_CoreTranslation`, `Maxcode_AmastyTranslation` et
`Maxcode_MageplazaTranslation`) : d'où la version 2.

### Ajouté
- 10 454 traductions, un fichier par éditeur, qui complètent le pack communautaire
  `community-engineering/language-fr_fr` (0.0.64 et suivantes) sans jamais l'écraser :
  - cœur Magento : `core.csv` (2 760) ;
  - Amasty (1 706), MageWorx (1 666), Magezon (745), Mageplaza (572), Anowave (440),
    Smartwave Porto (424), Xtento (414), Magefan (344), Webkul (251), PayPal Braintree (224),
    Swissup Breeze (194), Mirasvit (127), Fooman (111), Smile ElasticSuite (86), BSS (75),
    Payplug (65), Mageprince (54), MagePal (50), Aregowe (33), Baldwin (29), Yireo (21),
    Nukium GLS (18), Lyra Systempay (16), Bold (12), Fintecture (7), AVS (5), Blackbird (4).
- `corrections-communautaire.csv` : seules surcharges admises du pack communautaire, ses
  fautes avérées, chacune proposée en amont (`docs/corrections-communautaire.md`).
- Typographie française contrôlée : apostrophe `’`, espace insécable avant `: ; ! ?` et dans
  `« »`, points de suspension `…`, paramètres et balises conservés.
- Outillage de contrôle et CI : une clé déjà traduite par le pack communautaire, ou par le
  module qui l'utilise (son propre `i18n/fr_FR.csv`), reste hors du pack ; les chaînes
  comparées en dur dans du JavaScript et toute forme de secret sont refusées.

### Prérequis
- Magento 2.4 (`magento/framework` ^103.0) et `community-engineering/language-fr_fr` >= 0.0.64.
- Recommandé : `maxcode/module-translation-fixes`, pour les chaînes que Magento n'expose pas
  à la traduction.
