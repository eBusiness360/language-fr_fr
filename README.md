<div align="center">

**English** · [Français](README.fr.md)

# Maxcode language-fr_fr: French complement for Magento 2

**Translates what the community language pack leaves out, for the Magento core and common third-party modules.**

![License: MIT](https://img.shields.io/badge/license-MIT-1E4FA3)
![Magento 2.4](https://img.shields.io/badge/Magento-2.4.x-F26322)
![Packagist](https://img.shields.io/packagist/v/maxcode/language-fr_fr?color=0F1E4D)

</div>

## What it does

Translates into French what the community pack
[`community-engineering/language-fr_fr`](https://github.com/magento-l10n/language-fr_FR) does not translate: the rest of
the Magento core and common third-party modules (Amasty, Mageplaza, Mirasvit, MageWorx, Magefan, Smile, Fooman, BSS,
Xtento, Magezon, Porto, Swissup Breeze…). It installs **on top of** the community pack and changes none of its
translations, except its proven mistakes, which are gathered in `corrections-communautaire.csv` and proposed upstream.
It also leaves untouched the French translations that modules ship themselves.

## Installation

```bash
composer require maxcode/language-fr_fr
bin/magento setup:upgrade
bin/magento cache:clean translate
bin/magento setup:static-content:deploy fr_FR   # in production mode
```

Optional, for the strings Magento does not expose to translation:

```bash
composer require maxcode/module-translation-fixes
```

Translations of modules that are not in your store have no effect.

## Contributing

A key goes into the pack only if the community pack does not translate it, nor the module that uses it (its own
`i18n/fr_FR.csv`). One file per vendor (`amasty.csv`, `mageworx.csv`…), `core.csv` for the core. A mistake of the
community pack is fixed in `corrections-communautaire.csv`, with its line in `docs/corrections-communautaire.md`.
Before any proposal:

```bash
outils/php outils/telecharger-communautaire.php
outils/php outils/verifier.php
```

The typographic rules (apostrophe `’`, no-break space before `: ; ! ?`, `« »`, `…`, variables kept) are checked by the
CI.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT, © 2026 eBusiness360 – Maxime LESGUILLIER. See [LICENSE](LICENSE).
