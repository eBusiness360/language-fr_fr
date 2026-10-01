<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Ce qui doit se retrouver a l'identique entre la cle et sa traduction :
 * parametres %1 %s %d, directives {{...}}, et noms de balises HTML.
 */
final class Variables
{
    /** @return list<string> */
    public static function extraire(string $s): array
    {
        preg_match_all('/%\d+|%[sd]|\{\{.*?\}\}|<\/?[a-zA-Z][a-zA-Z0-9]*/s', $s, $m);
        $v = array_map(
            static fn (string $x): string => $x[0] === '<' ? strtolower($x) : $x,
            $m[0]
        );
        sort($v, SORT_STRING);

        return $v;
    }
}
