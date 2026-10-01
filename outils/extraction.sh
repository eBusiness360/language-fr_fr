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
(cd "$site" && ddev exec php -d memory_limit=2G var/pack-fr/outils/extraire.php)
rm -rf "$pack/outils/a-traduire/sites/$nom"
mkdir -p "$pack/outils/a-traduire/sites"
cp -r "$site/var/pack-fr/sortie" "$pack/outils/a-traduire/sites/$nom"
rm -rf "$site/var/pack-fr"
echo "Extraction de $nom rapatriée dans outils/a-traduire/sites/$nom"
