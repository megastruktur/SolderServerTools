<?php

require_once('./vendor/autoload.php');

$modpack_slug = $argv[1] ?? 'mymodpack';
$modpack_version = $argv[2] ?? '1.0.0';

$MP = new \SolderServerTools\Modpack($modpack_slug, $modpack_version);
$MP->buildForgeServer();

echo "\n";
