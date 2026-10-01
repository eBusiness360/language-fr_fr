<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

/** Fait entrer les lignes traduites d'une liste de travail dans le fichier d'un editeur. */
final class Integration
{
    /**
     * @param array<string, string> $existant cle => traduction du fichier de l'editeur
     * @param list<array{cle: string, traduction: string, module: string, zone: string}> $lignes
     * @param array<string, string> $autresFichiers cle => nom du fichier qui la porte deja
     * @param array<string, string> $communautaire cle => traduction communautaire
     * @return array{entrees: array<string, string>, front: list<string>, refusees: list<array{cle: string, raison: string}>}
     */
    public static function integrer(array $existant, array $lignes, array $autresFichiers, array $communautaire): array
    {
        $entrees = $existant;
        $front = [];
        $refusees = [];
        foreach ($lignes as $l) {
            if (trim($l['traduction']) === '') {
                continue;
            }
            if (isset($autresFichiers[$l['cle']])) {
                $refusees[] = ['cle' => $l['cle'], 'raison' => 'déjà dans ' . $autresFichiers[$l['cle']]];
                continue;
            }
            if (isset($communautaire[$l['cle']]) && $communautaire[$l['cle']] !== $l['cle']) {
                $refusees[] = ['cle' => $l['cle'], 'raison' => 'traduite par le pack communautaire'];
                continue;
            }
            $entrees[$l['cle']] = Normaliseur::normaliser($l['traduction']);
            if ($l['zone'] === 'front') {
                $front[] = $l['cle'];
            }
        }

        return ['entrees' => $entrees, 'front' => $front, 'refusees' => $refusees];
    }
}
