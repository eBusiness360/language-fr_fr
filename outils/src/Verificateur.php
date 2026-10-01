<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/**
 * Applique les regles du pack (spec § 5). Une erreur bloque la publication ;
 * un avertissement signale ce qui merite un regard humain.
 */
final class Verificateur
{
    /**
     * Seul fichier autorise a surcharger le pack communautaire : ses fautes
     * averees, chacune signalee en amont (docs/corrections-communautaire.md).
     */
    public const CORRECTIONS = 'corrections-communautaire.csv';

    /**
     * @param array<string, string> $communautaire cle => traduction du pack communautaire
     * @param list<string> $interdites
     * @param list<string> $identiquesAutorises
     * @param array<string, string> $glossaire terme anglais (minuscules) => terme traduit
     */
    public function __construct(
        private readonly array $communautaire,
        private readonly array $interdites,
        private readonly array $identiquesAutorises,
        private readonly array $glossaire,
    ) {
    }

    /**
     * @param array<string, string> $fichiers nom => chemin
     * @return list<Constat>
     */
    public function verifierPack(array $fichiers): array
    {
        $constats = [];
        $vuesPack = [];

        foreach ($fichiers as $nom => $chemin) {
            array_push($constats, ...$this->verifierOctets($nom, $chemin));
            $vuesFichier = [];

            foreach (Csv::lire($chemin) as $e) {
                [$cle, $trad, $ligne] = [$e['cle'], $e['traduction'], $e['ligne']];
                $erreur = static fn (string $regle, string $message): Constat
                    => new Constat('erreur', $nom, $ligne, $regle, $message . ' — « ' . mb_substr($cle, 0, 60) . ' »');

                if ($e['champs'] !== 2) {
                    $constats[] = $erreur('format', sprintf('%d champ(s) au lieu de 2 (guillemet non doublé ?)', $e['champs']));
                    continue;
                }
                if (isset($vuesFichier[$cle])) {
                    $constats[] = $erreur('doublon', 'clé déjà présente ligne ' . $vuesFichier[$cle]);
                } elseif (isset($vuesPack[$cle])) {
                    $constats[] = $erreur('doublon-fichiers', 'clé déjà présente dans ' . $vuesPack[$cle]);
                }
                $vuesFichier[$cle] = $ligne;
                $vuesPack[$cle] ??= $nom;

                if (trim($trad) === '') {
                    $constats[] = $erreur('vide', 'traduction vide');
                    continue;
                }
                $traduiteParLeCommunautaire = isset($this->communautaire[$cle]) && $this->communautaire[$cle] !== $cle;
                if ($nom === self::CORRECTIONS) {
                    if (!$traduiteParLeCommunautaire) {
                        $constats[] = $erreur('correction-sans-objet', 'le pack communautaire ne la traduit pas : la ranger dans le fichier de l’éditeur');
                    } elseif ($this->communautaire[$cle] === $trad) {
                        $constats[] = $erreur('correction-integree', 'le pack communautaire a intégré la correction : la retirer');
                    }
                } elseif ($traduiteParLeCommunautaire) {
                    $constats[] = $erreur('communautaire', 'déjà traduite par le pack communautaire');
                }
                if ($trad === $cle && !in_array($cle, $this->identiquesAutorises, true)) {
                    $constats[] = $erreur('identique', 'traduction identique à l’anglais');
                }
                if (in_array($cle, $this->interdites, true)) {
                    $constats[] = $erreur('interdite', 'chaîne comparée en dur dans du JavaScript');
                }
                if (Variables::extraire($cle) !== Variables::extraire($trad)) {
                    $constats[] = $erreur('variables', 'paramètres, directives ou balises différents');
                }
                foreach (Typographie::erreurs($trad) as $t) {
                    $constats[] = $erreur($t['regle'], $t['message']);
                }
                foreach (Typographie::avertissements($trad) as $t) {
                    $constats[] = new Constat('avertissement', $nom, $ligne, $t['regle'], $t['message'] . ' — « ' . mb_substr($trad, 0, 60) . ' »');
                }
                $normalise = str_replace('’', "'", mb_strtolower($trad));
                // Le vocabulaire se juge sur le texte : pas dans %customer_name,
                // {{var customer}} ni dans les attributs des balises.
                $texteCle = (string) preg_replace('/%[a-zA-Z_]\w*/', ' ', Typographie::texteVisible($cle));
                foreach ($this->glossaire as $en => $traduit) {
                    if (preg_match('/(?<![\p{L}_])' . preg_quote((string) $en, '/') . '(?![\p{L}_])/iu', $texteCle)
                        && !str_contains($normalise, str_replace('’', "'", $traduit))) {
                        $constats[] = new Constat('avertissement', $nom, $ligne, 'glossaire',
                            sprintf('« %s » se traduit « %s » (glossaire) — « %s »', $en, $traduit, mb_substr($cle, 0, 60)));
                    }
                }
            }
        }

        return $constats;
    }

    /** @return list<Constat> */
    private function verifierOctets(string $nom, string $chemin): array
    {
        $brut = (string) file_get_contents($chemin);
        $c = [];
        if (str_starts_with($brut, "\xEF\xBB\xBF")) {
            $c[] = new Constat('erreur', $nom, 1, 'fichier', 'BOM UTF-8 à retirer');
        }
        if (!mb_check_encoding($brut, 'UTF-8')) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'encodage autre que UTF-8');
        }
        if (str_contains($brut, "\r")) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'fins de ligne Windows (\r)');
        }
        if ($brut !== '' && !str_ends_with($brut, "\n")) {
            $c[] = new Constat('erreur', $nom, 0, 'fichier', 'pas de saut de ligne final');
        }

        return $c;
    }
}
