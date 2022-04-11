<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
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
      $full_name = array_key_first($repo_info);
      $repo = reset($repo_info);
      return $this->mapToCommonFormat($full_name, $repo['label'], $repo['description'], $repo['num_open_issues'], 'yaml', $uri);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validate($uri) {
    $pattern = '/^(https?:\/\/)[a-zA-Z0-9_\-\/\.]+\.yml/';

    if (preg_match($pattern, $uri) == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText() {
    return 'https://anything.anything/anything/anything.yml (or "http")';
  }

}
