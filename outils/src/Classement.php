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
