<?php

namespace SolderServerTools;

use PHPHtmlParser\Dom;

class Forge {

  protected $forge_url_prefix;
  protected $minecraft_version;
  protected $build;
  protected $downloads_path;
  protected $build_indexes = [
    'latest' => 0,
    'stable' => 1,
  ];
  protected $is_test = TRUE;

  public $forge_download_link = '';
  public $forge_installer_filename = '';

  function __construct() {

    $config = parse_ini_file('./config.ini');
    $this->forge_url_prefix = $config['forge_url_prefix'];
    $this->minecraft_version = $config['minecraft_version'];
    $this->build = $config['build'];
    $this->downloads_path = $config['downloads_path'] . '/forge';
  }

  /**
   * Retrieve Download Link.
   */
  protected function getForgeDownloadLink() {

    $download_url = FALSE;
    $installer_local_file_path = FALSE;
    
    $dom = new Dom;

    if ($this->is_test) {
      $dom->loadFromFile('./tests/assets/index_1.7.10.html');
    }
    else {
      $url = $this->forge_url_prefix . $this->minecraft_version . '.html';
      $dom->loadFromUrl($url);
    }
  
    $contents = $dom->find('.download a[title="Installer"]');
  
    // Get necessary installer link depending on build and MC version.
    if (count($contents) == 2) {
      $href = $contents[$this->build_indexes[$this->build]]->getAttribute('href');
      preg_match('/&url=(https:\/\/.*\.jar)/', $href, $matches);
  
      if ($matches && isset($matches[1])) {

        $this->forge_download_link = $matches[1];
  
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
  protected function downloadForgeInstaller() {

    $this->getForgeDownloadLink();
  
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
    }
    else {
      throw new \Exception('No Forge filename parsed');
    }

  }

  /**
   * Install Forge Server.
   */
  public function installForgeServer($build_directory) {

    $this->downloadForgeInstaller();

    if ($this->forge_installer_filename) {

      $installer_local_file_path = $this->downloads_path . '/' . $this->forge_installer_filename;

      if ($installer_local_file_path && file_exists($installer_local_file_path)) {

        if (!file_exists($build_directory)) {
          mkdir($build_directory);
        }
        copy($installer_local_file_path, $build_directory . '/' . $this->forge_installer_filename);

        // Simply run the command.
        exec('cd "' . $build_directory . '"; java -jar ' . $this->forge_installer_filename . ' --installServer');

        // And remove the installer.
        unlink($build_directory . '/' . $this->forge_installer_filename);
      }
    }
    else {
      throw new \Exception('No Forge filename parsed');
    }
  }

}