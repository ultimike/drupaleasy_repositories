<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\Component\Serialization\Yaml;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yaml_remote",
 *   label = @Translation("Remote Yaml file"),
 *   description = @Translation("Remote .yaml file that includes repository metadata")
 * )
 */
class YamlRemote extends DrupaleasyRepositoriesPluginBase {

  /**
   * Callback function for array_walk to add source value.
   *
   * @param array $item
   *   The array item.
   * @param string $key
   *   The array key.
   * @param string $uri
   *   The uri of the repository.
   */
  protected function addSourceAndUri(array &$item, string $key, string $uri) {
    // This needs to be the same as the plugin ID.
    $item['source'] = 'yaml';
    $item['url'] = $uri;
  }

  /**
   * Gets a single repository from the .yml file.
   *
   * @param string $uri
   *   The URI of the repository to get.
   *
   * @return array
   *   The repositories.
   */
  public function getRepo(string $uri) {
    if ($file_content = file_get_contents($uri)) {
      $repo_info = Yaml::decode($file_content);
      array_walk($repo_info, ['self', 'addSourceAndUri'], $uri);
      return $repo_info;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($uri) {
    $pattern = '/^(https:\/\/)[a-zA-Z0-9_\-\/\.]+\.yml/';

    if (preg_match($pattern, $uri) == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText() {
    return 'https://anything.anything/anything/anything.yml';
  }

}
