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

    /**
     * Composants hors du pack (outils/exclus.txt) : les modules Maxcode portent
     * leurs propres chaines (« un module voyage avec ses chaines »), les themes
     * propres a un client n'ont rien a faire dans un pack universel.
     *
     * @param list<string> $prefixes
     */
    public static function exclu(string $composant, array $prefixes): bool
    {
        foreach ($prefixes as $p) {
            if (str_starts_with($composant, $p)) {
                return true;
            }
        }

        return false;
    }
}
