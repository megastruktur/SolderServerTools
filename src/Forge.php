<?php

namespace SolderServerTools;

use \PHPHtmlParser\Dom;

class Forge {

  protected $forge_url_prefix;
  protected $build;
  protected $downloads_path;
  protected $build_indexes = [
    'latest' => 0,
    'stable' => 1,
  ];
  
  // Set to TRUE to get the parsed page from tests/assets
  protected $is_test = FALSE;

  public $forge_download_link = '';
  public $forge_installer_filename = '';

  function __construct() {

    $config = parse_ini_file('./config.ini');
    $this->forge_url_prefix = $config['forge_url_prefix'];
    $this->build = $config['build'];
    $this->downloads_path = $config['downloads_path'] . '/forge';
  }

  /**
   * Retrieve Download Link.
   */
  protected function getForgeDownloadLink($minecraft_version) {

    echo "2.1 Get Forge Download Link \n";
    $download_url = FALSE;
    $installer_local_file_path = FALSE;
    
    $dom = new Dom;

    if ($this->is_test) {
      $dom->loadFromFile('./tests/assets/index_1.7.10.html');
    }
    else {
      $url = $this->forge_url_prefix . $minecraft_version . '.html';
      $dom->loadFromUrl($url);
    }
  
    $contents = $dom->find('.download a[title="Installer"]');
  
    // Get necessary installer link depending on build and MC version.
    if (count($contents) == 2) {
      $href = $contents[$this->build_indexes[$this->build]]->getAttribute('href');
      preg_match('/&url=(https:\/\/.*\.jar)/', $href, $matches);
  
      if ($matches && isset($matches[1])) {        
        $this->forge_download_link = $matches[1];

        echo "Link: $this->forge_download_link \n\n";
  
        preg_match('/\/(forge-.*\.jar)/', $this->forge_download_link, $filename_match);
  
        if ($filename_match && isset($filename_match[1])) {

          $this->forge_installer_filename = $filename_match[1];
          return TRUE;
        }
      }
    }

    throw new \Exception('Nothing to parse. Error.');
  }

  /**
   * Download Forge Installer.
   */
  protected function downloadForgeInstaller($minecraft_version) {

    $this->getForgeDownloadLink($minecraft_version);

    echo "2.2 Downloading Forge Installer... ";
  
    // If filename found - download if necessary or take from cache.
    if ($this->forge_installer_filename) {

      $installer_local_file_path = $this->downloads_path . '/' . $this->forge_installer_filename;
  
      // If file doesn't exist - download.
      if (!file_exists($installer_local_file_path)) {
  
        $ch = curl_init($this->forge_download_link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        file_put_contents($installer_local_file_path, $data);
      }
      echo "... Done \n\n";
    }
    else {
      throw new \Exception('No Forge filename parsed');
    }

  }

  /**
   * Install Forge Server.
   * @param Modpack $MP
   */
  public function installForgeServer($MP) {

    $this->downloadForgeInstaller($MP->minecraft_version);

    echo "2.3 Installing Forge Server... ";
    if ($this->forge_installer_filename) {

      $installer_local_file_path = $this->downloads_path . '/' . $this->forge_installer_filename;

      if ($installer_local_file_path && file_exists($installer_local_file_path)) {

        if (!file_exists($MP->modpack_version_dir)) {
          mkdir($MP->modpack_version_dir);
        }
        copy($installer_local_file_path, $MP->modpack_version_dir . '/' . $this->forge_installer_filename);

        // Simply run the command.
        exec('cd "' . $MP->modpack_version_dir . '"; java -jar ' . $this->forge_installer_filename . ' --installServer');

        // And remove the installer.
        unlink($MP->modpack_version_dir . '/' . $this->forge_installer_filename);
      }

      echo "... Done \n\n";
    }
    else {
      throw new \Exception('No Forge filename parsed');
    }
  }

}
