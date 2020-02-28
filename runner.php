<?php

require_once('./vendor/autoload.php');

// Download and install Forge server.
$config = parse_ini_file('./config.ini');
$build_directory = $config['builds_path'] . '/' . date('Y-m-d H:i:s', time());

$Forge = new \SolderServerTools\Forge();
$Forge->installForgeServer($build_directory);

$Solder = new \SolderServerTools\SolderConnector();
$Solder->buildMods($build_directory);

echo "\n";