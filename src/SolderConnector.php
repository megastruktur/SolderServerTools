<?php
 
namespace SolderServerTools;

require_once('./vendor/autoload.php');

use \Httpful\Httpful;

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
    $this->solder_base_url = $config['solder_url'] . '/api/modpack';
  }

  /**
   * Call Solder endpoints.
   */
  protected function call($endpoint = '/') {

    $uri = $this->solder_base_url . $endpoint;
    $url = $uri . '?' . http_build_query(['k' => $this->solder_api_key]);
    $r = \Httpful\Request::get($url)
      ->expectsJson()
      ->send();

    return $r->body;
  }

  /**
   * Get Active modpack versions.
   */
  public function getVersions() {
    
    $results = $this->call();

    if ($results && $results->builds) {
      return $results->builds;
    }

    return FALSE;
  }
  

  /**
   * Get Modpack Data
   */
  public function getModpackData($modpack_slug, $version) {
    return $this->call('/' . $modpack_slug . '/' . $version);
  }

}
