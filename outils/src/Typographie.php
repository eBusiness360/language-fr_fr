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

        // Majuscules de titre (« Ajouter Au Panier ») : au moins deux mots
        // CONSECUTIFS capitalises en milieu de phrase. Ne comptent ni le
        // premier mot d'une phrase ou d'un libelle cite (apres . : ! ? «), ni
        // les sigles, ni les marques en casse mixte (PayPal) ; un nom propre
        // isole (« Avis Amasty ») ne declenche donc rien.
        $mots = preg_split('/\s+/u', trim($t)) ?: [];
        $suite = 0;
        $titre = false;
        foreach ($mots as $i => $mot) {
            $debut = $i === 0 || preg_match('/[.:!?«]$/u', $mots[$i - 1]);
            if (!$debut && preg_match('/^\p{Lu}\p{Ll}+[,;]?$/u', $mot)) {
                $titre = $titre || ++$suite >= 2;
            } else {
                $suite = 0;
            }
        }
        if ($titre) {
            $a[] = ['regle' => 'majuscules', 'message' => 'majuscules de titre : en français, seule la première lettre de la phrase'];
        }

        return $a;
    }
}
