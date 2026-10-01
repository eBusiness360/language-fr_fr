# Changelog — maxcode/language-fr_fr

Format : [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions : [SemVer](https://semver.org/lang/fr/).

## [Non publié]

### Ajouté
- Organisation en un fichier CSV par éditeur, outillage de contrôle et CI.
- `corrections-communautaire.csv` : seules surcharges admises du pack
  communautaire, ses fautes avérées, chacune proposée en amont
  (`docs/corrections-communautaire.md`). Le vérificateur signale une correction
  sans objet ou intégrée en amont.

### Modifié
- Les clés que le module qui les utilise traduit déjà lui-même (son
  `i18n/fr_FR.csv`) ne sont plus dans le pack : chargé après le module, il
  écrasait sa traduction (Payplug, Mirasvit, Magefan Blog, Payment Services…).
