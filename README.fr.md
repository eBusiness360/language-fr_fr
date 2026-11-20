<div align="center">

[English](README.md) · **Français**

# Maxcode language-fr_fr : complément français pour Magento 2

**Traduit ce que le pack communautaire laisse de côté, pour le cœur de Magento et les modules tiers courants.**

![Licence : MIT](https://img.shields.io/badge/licence-MIT-1E4FA3)
![Magento 2.4](https://img.shields.io/badge/Magento-2.4.x-F26322)
![Packagist](https://img.shields.io/packagist/v/maxcode/language-fr_fr?color=0F1E4D)

</div>

## Ce que fait le pack

Traduit ce que le pack communautaire [`community-engineering/language-fr_fr`](https://github.com/magento-l10n/language-fr_FR)
ne traduit pas : le reste du cœur Magento et les modules tiers courants (Amasty, Mageplaza,
Mirasvit, MageWorx, Magefan, Smile, Fooman, BSS, Xtento, Magezon, Porto, Swissup Breeze…).
Il s'installe **par-dessus** le pack communautaire et ne modifie aucune de ses traductions,
sauf ses fautes avérées, regroupées dans `corrections-communautaire.csv` et proposées en amont.
Il laisse aussi intactes les traductions françaises que les modules livrent eux-mêmes.

## Installation

```bash
composer require maxcode/language-fr_fr
bin/magento setup:upgrade
bin/magento cache:clean translate
bin/magento setup:static-content:deploy fr_FR   # en mode production
```

Facultatif, pour les chaînes que Magento n'expose pas à la traduction :

```bash
composer require maxcode/module-translation-fixes
```

Les traductions de modules absents de votre boutique sont sans effet.

## Contribuer

Une clé n'entre dans le pack que si le pack communautaire ne la traduit pas, ni le module qui
l'utilise (son propre `i18n/fr_FR.csv`). Un fichier par éditeur (`amasty.csv`, `mageworx.csv`…),
`core.csv` pour le cœur. Une faute du pack communautaire se corrige dans
`corrections-communautaire.csv`, avec sa ligne dans `docs/corrections-communautaire.md`.
Avant toute proposition :

```bash
outils/php outils/telecharger-communautaire.php
outils/php outils/verifier.php
```

Les règles (apostrophe `’`, espace insécable avant `: ; ! ?`, `« »`, `…`, variables conservées)
sont contrôlées par la CI.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md).

## Licence

MIT, © 2026 eBusiness360 – Maxime LESGUILLIER. Voir [LICENSE](LICENSE).
