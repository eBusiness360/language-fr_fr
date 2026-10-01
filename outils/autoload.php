<?php
declare(strict_types=1);

spl_autoload_register(static function (string $classe): void {
    $prefixe = 'Maxcode\\LanguagePack\\Outils\\';
    if (str_starts_with($classe, $prefixe)) {
        require __DIR__ . '/src/' . substr($classe, strlen($prefixe)) . '.php';
    }
});
