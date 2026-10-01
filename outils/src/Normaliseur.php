<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Corrige automatiquement la typographie DU FRANCAIS dans le texte visible
 * d'une traduction ; balises, directives {{...}} et entites sont laissees
 * intactes. Idempotent.
 */
final class Normaliseur
{
    public static function normaliser(string $t): string
    {
        $segments = preg_split('/(<[^>]*>|\{\{.*?\}\}|&[a-zA-Z]+;|&#\d+;)/su', $t, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$t];
        foreach ($segments as $i => $s) {
            if ($i % 2 === 1) {
                continue; // balisage capture : intact
            }
            $s = str_replace("'", '’', $s);
            $s = str_replace('...', '…', $s);
            $s = (string) preg_replace('/"([^"]*)"/u', '«' . Typographie::NBSP . '$1' . Typographie::NBSP . '»', $s);
            $s = (string) preg_replace('/«[ \x{00A0}]*/u', '«' . Typographie::NBSP, $s);
            $s = (string) preg_replace('/[ \x{00A0}]*»/u', Typographie::NBSP . '»', $s);
            $s = (string) preg_replace('/ ([:;!?])/u', Typographie::NBSP . '$1', $s);
            $s = (string) preg_replace('/(\p{L})([:;!?])(?=\s|$)/u', '$1' . Typographie::NBSP . '$2', $s);
            $segments[$i] = $s;
        }

        return implode('', $segments);
    }
}
