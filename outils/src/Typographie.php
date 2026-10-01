<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Regles typographiques DU FRANCAIS (langue.json : "typographie": "fr").
 * Elles ne portent que sur le texte VISIBLE : balises HTML, directives {{...}}
 * et entites &...; en sont retirees, sauf pour l'apostrophe, interdite
 * partout (une apostrophe droite dans un attribut casse aussi Knockout).
 */
final class Typographie
{
    public const NBSP = "\u{00A0}";

    public static function texteVisible(string $s): string
    {
        $s = (string) preg_replace('/\{\{.*?\}\}/su', ' ', $s);
        $s = (string) preg_replace('/<[^>]*>/su', ' ', $s);

        return (string) preg_replace('/&[a-zA-Z]+;|&#\d+;/u', ' ', $s);
    }

    /** @return list<array{regle: string, message: string}> */
    public static function erreurs(string $traduction): array
    {
        $e = [];
        $t = self::texteVisible($traduction);

        if (str_contains($traduction, "'")) {
            $e[] = ['regle' => 'apostrophe', 'message' => "apostrophe droite ' : utiliser ’ (U+2019)"];
        }
        if (preg_match('/ [:;!?]/u', $t) || preg_match('/\p{L}[:;!?](?=\s|$)/u', $t)) {
            $e[] = ['regle' => 'espace-ponctuation', 'message' => 'avant : ; ! ? il faut une espace insécable (U+00A0)'];
        }
        if (str_contains($t, '"') || preg_match('/«(?!\x{00A0})|(?<!\x{00A0})»/u', $t)) {
            $e[] = ['regle' => 'guillemets', 'message' => 'guillemets : « » avec insécables, " seulement dans le balisage'];
        }
        if (str_contains($t, '...')) {
            $e[] = ['regle' => 'suspension', 'message' => '... : utiliser … (U+2026)'];
        }

        return $e;
    }

    /** @return list<array{regle: string, message: string}> */
    public static function avertissements(string $traduction): array
    {
        $a = [];
        $t = self::texteVisible($traduction);

        if (preg_match('/(?<!\p{L})(tu|toi|ton|ta|tes)(?!\p{L})/iu', $t)) {
            $a[] = ['regle' => 'tutoiement', 'message' => 'tutoiement : le vouvoiement est la règle côté client'];
        }

        // Majuscules de titre : au moins deux mots, hors le premier, qui
        // commencent par une majuscule sans etre des sigles ni des marques
        // en casse mixte (PayPal).
        $mots = preg_split('/\s+/u', trim($t)) ?: [];
        $titres = 0;
        foreach (array_slice($mots, 1) as $mot) {
            if (preg_match('/^\p{Lu}\p{Ll}+$/u', $mot)) {
                $titres++;
            }
        }
        if ($titres >= 2) {
            $a[] = ['regle' => 'majuscules', 'message' => 'majuscules de titre : en français, seule la première lettre de la phrase'];
        }

        return $a;
    }
}
