# Complément français pour Magento 2

Traduit ce que le pack communautaire [`community-engineering/language-fr_fr`](https://github.com/magento-l10n/language-fr_FR)
ne traduit pas : le reste du cœur Magento et les modules tiers courants (Amasty, Mageplaza,
Mirasvit, MageWorx, Magefan, Smile, Fooman, BSS, Xtento, Magezon, Porto, Swissup Breeze…).
Il s'installe **par-dessus** le pack communautaire et ne modifie aucune de ses traductions,
sauf ses fautes avérées, regroupées dans `corrections-communautaire.csv` et proposées en amont.
Il laisse aussi intactes les traductions françaises que les modules livrent eux-mêmes.

## Installation

    composer require maxcode/language-fr_fr
    bin/magento setup:upgrade
    bin/magento cache:clean translate
    bin/magento setup:static-content:deploy fr_FR   # en mode production

Facultatif — pour les chaînes que Magento n'expose pas à la traduction :

    composer require maxcode/module-translation-fixes

Les traductions de modules absents de votre boutique sont sans effet.

## Contribuer

Une clé n'entre dans le pack que si le pack communautaire ne la traduit pas, ni le module qui
l'utilise (son propre `i18n/fr_FR.csv`). Un fichier par éditeur (`amasty.csv`, `mageworx.csv`…),
`core.csv` pour le cœur. Une faute du pack communautaire se corrige dans
`corrections-communautaire.csv`, avec sa ligne dans `docs/corrections-communautaire.md`.
Avant toute proposition :

    outils/php outils/telecharger-communautaire.php
    outils/php outils/verifier.php

Les règles (apostrophe `’`, espace insécable avant `: ; ! ?`, `« »`, `…`, variables conservées)
sont contrôlées par la CI.

## Licence

MIT — © 2026 eBusiness360 – Maxime LESGUILLIER.
