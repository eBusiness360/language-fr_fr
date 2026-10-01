<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Tout ce qui depend de la langue du pack, lu dans outils/langue.json. Les
 * outils n'ecrivent jamais « fr_FR » en dur : pour un pack dans une autre
 * langue, on copie outils/, on change ce fichier, le glossaire et, si la
 * langue a ses propres regles, la typographie.
 */
final class Langue
{
    private function __construct(
        public readonly string $locale,
        public readonly string $composant,
        public readonly string $paquet,
        public readonly string $paquetCommunautaire,
        public readonly string $depotCommunautaire,
        public readonly string $fichierCommunautaire,
        public readonly string $typographie,
    ) {
    }

    public static function charger(?string $fichier = null): self
    {
        $fichier ??= dirname(__DIR__) . '/langue.json';
        $d = json_decode((string) @file_get_contents($fichier), true);
        $c = is_array($d) ? ($d['communautaire'] ?? null) : null;
        foreach (['locale', 'composant', 'paquet', 'typographie'] as $champ) {
            if (!is_string($d[$champ] ?? null) || $d[$champ] === '') {
                throw new \RuntimeException("$fichier : champ « $champ » manquant");
            }
        }
        foreach (['paquet', 'depot', 'fichier'] as $champ) {
            if (!is_string($c[$champ] ?? null) || $c[$champ] === '') {
                throw new \RuntimeException("$fichier : champ « communautaire.$champ » manquant");
            }
        }

        return new self($d['locale'], $d['composant'], $d['paquet'], $c['paquet'], $c['depot'], $c['fichier'], $d['typographie']);
    }
}
