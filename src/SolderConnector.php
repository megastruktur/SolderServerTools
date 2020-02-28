<?php
 
namespace SolderServerTools;

use Httpful\Httpful;

require_once('./vendor/autoload.php');

class SolderConnector {

  protected $solder_base_url;
  protected $solder_api_key;
  protected $mods_download_path;
  protected $mods_build_download_path;

  public $latest_version = FALSE;
  public $mods = [];

  function __construct() {
    
    $config = parse_ini_file('./config.ini');
    $this->solder_api_key = $config['solder_api_key'];
    $this->solder_base_url = $config['solder_url'] . '/api/modpack/' . $config['solder_modpack_slug'];
    $this->mods_download_path = $config['downloads_path'] . '/mods_cache';
  }

  /**
   * Call Solder endpoints.
   */
  protected function call($endpoint = '') {

    $uri = $this->solder_base_url . $endpoint;
    $url = $uri . '?' . http_build_query(['k' => $this->solder_api_key]);
    $r = \Httpful\Request::get($url)
      ->expectsJson()
      ->send();

    return $r->body;
  }

  /**
   * Get Latest modpack version.
   */
  protected function getLatestVersion() {
    
    $results = $this->call();

    if ($results && $results->builds) {
      $this->latest_version = $results->builds['0'];
      $this->mods_build_download_path = $this->mods_download_path . '/' . $this->latest_version;
      if (!file_exists($this->mods_build_download_path)) {
        mkdir($this->mods_build_download_path, 0755, TRUE);
      }
      return $results->builds['0'];
    }

    return FALSE;
  }

  /**
   * Get array of mods for modpack.
   */
  protected function getModsList() {

    // Retrieve Latest Version if not yet called.
    if (!$this->latest_version) {
      $this->getLatestVersion();
    }

    // If version is set.
    if ($this->latest_version) {
      $results = $this->call('/' . $this->latest_version);

      if ($results && $results->mods) {

        $this->mods = $results->mods;
        return $results->mods;
      }
    }

    return FALSE;

  }

  /**
   * Downloads archives to mods directory if necessary.
   */
  protected function downloadMods() {

    $this->getLatestVersion();
    $this->getModsList();

    if ($this->mods) {

      foreach ($this->mods as $mod) {
        $mod_zip_filename = $mod->name . '-' . $mod->version . '.zip';
        $download_destination_path = $this->mods_build_download_path . '/' . $mod_zip_filename;
        if (!file_exists($download_destination_path)) {

          $ch = curl_init($mod->url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $data = curl_exec($ch);
          curl_close($ch);
          file_put_contents($download_destination_path, $data);
        }
      }

    }

  }

  /**
   * Unarchive and build.
   */
  public function buildMods($build_directory) {

    $this->downloadMods();

    $zip = new \ZipArchive;

    foreach ($this->mods as $mod) {
      $mod_zip_filename = $mod->name . '-' . $mod->version . '.zip';
      $mod_archive_path = $this->mods_build_download_path . '/' . $mod_zip_filename;

      $res = $zip->open($mod_archive_path);

      if ($res === TRUE) {
        $zip->extractTo($build_directory);
        $zip->close();
        echo "\n $mod_zip_filename extracted \n";
      } else {
        throw new \Exception('No file provided but needed');
      }

    }
  }

}