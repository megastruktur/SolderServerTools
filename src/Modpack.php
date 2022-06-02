<?php

namespace SolderServerTools;

require_once('./vendor/autoload.php');

class Modpack {

  public string $slug;
  public string $id;
  public string $version;
  public string $minecraft_version;
  protected array $config;
  public string $modpack_version_dir;

  /**
   * Aray of objects with mods.
   * e.g.
   *     [0]=>
      object(stdClass)#14 (6) {
        ["id"]=>
        int(123)
        ["name"]=>
        string(19) "advent-of-ascension"
        ["version"]=>
        string(12) "1.16.5-3.6.5"
        ["md5"]=>
        string(32) "a32cc5814fe2e6e009cfa1f17555ee9c"
        ["filesize"]=>
        int(129851005)
        ["url"]=>
        string(93) "https://solder-repo.spcrew.club/mods/advent-of-ascension/advent-of-ascension-1.16.5-3.6.5.zip"
      }
   */
  protected array $mods;

  function __construct($slug, $version) {

    $this->config = parse_ini_file('./config.ini');
    $this->slug = $slug;
    $this->version = $version;

    $SC = new \SolderServerTools\SolderConnector();
    $modpack_data = $SC->getModpackData($this->slug, $this->version);

    $this->mods = $modpack_data->mods;
    $this->minecraft_version = $modpack_data->minecraft;

  }

  /**
   * Create necessary directories for future build.
   */
  protected function createModpackDirs() {

    $modpack_dir = $this->config['builds_path'] . '/' . $this->slug;
    $this->modpack_version_dir = $modpack_dir . '/' . $this->version;

    if (!file_exists($modpack_dir)) {
      mkdir($modpack_dir);
    }
    if (!file_exists($this->modpack_version_dir)) {
      mkdir($this->modpack_version_dir);
    }
  }

  /**
   * Cache all mods.
   *
   * @return void
   */
  protected function cacheMods() {

    echo "1.1 Caching mods... \n\n";
    foreach ($this->mods as $mod) {
      $mod_zip_filename = $mod->name . '-' . $mod->version . '.zip';
      $download_destination_path = $this->config['downloads_path'] . '/mods_cache/' . $mod_zip_filename;
      if (!file_exists($download_destination_path)) {
        echo '- Downloaded mod: ' . $mod->name . '-' . $mod->version . PHP_EOL;
        file_put_contents($download_destination_path, file_get_contents($mod->url));
      }
    }
    echo "... Done \n\n";

  }

  /**
   * Unarchive and build.
   */
  protected function buildMods() {

    $this->cacheMods();
    $this->createModpackDirs();

    $zip = new \ZipArchive;

    echo "1.2 Extracting Mods... \n\n";

    foreach ($this->mods as $mod) {
      $mod_zip_filename = $mod->name . '-' . $mod->version . '.zip';
      $mod_archive_path = $this->config['downloads_path'] . '/mods_cache/' . $mod_zip_filename;

      $res = $zip->open($mod_archive_path);

      if ($res === TRUE) {
        $zip->extractTo($this->modpack_version_dir);
        $zip->close();
        echo "- $mod_zip_filename extracted" . PHP_EOL;
      } else {
        echo "\n-------------------------- \n";
        throw new \Exception('No file provided but needed');
        echo "\n-------------------------- \n";
      }

    }
    echo "... Done \n\n";
  }

  /**
   * Build Forge Server.
   *
   * @return void
   */
  public function buildForgeServer() {

    echo "\n-------------------------- \n\n";
    echo "Process Started! \n\n";
    echo "\n-------------------------- \n\n";
    echo "1. Building mods \n\n";
    $this->buildMods();
    echo "\n-------------------------- \n\n";

    echo "2. Building Forge \n\n";
    $Forge = new \SolderServerTools\Forge();
    $Forge->installForgeServer($this);

    echo "\n-------------------------- \n\n";
    
    echo "DONE! \n";
  }
}
