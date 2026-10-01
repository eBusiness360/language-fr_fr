<?php
declare(strict_types=1);

namespace Maxcode\LanguagePack\Outils;

final class Constat
{
    public function __construct(
        public readonly string $niveau,
        public readonly string $fichier,
        public readonly int $ligne,
        public readonly string $regle,
        public readonly string $message,
    ) {
    }
}
