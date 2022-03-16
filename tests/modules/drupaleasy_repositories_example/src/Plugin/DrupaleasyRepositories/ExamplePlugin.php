<?php

namespace Drupal\drupaleasy_repositories_example\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "drupaleasy_repositories_example",
 *   label = @Translation("Example plugin"),
 *   description = @Translation("Example plugin for DrupalEasy Repositories tests.")
 * )
 */
class ExamplePlugin extends DrupaleasyRepositoriesPluginBase {

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
    $item['source'] = 'example';
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validate($uri) {
    if (strlen($uri) > 5) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText() {
    return 'URL must be longer than 5 characters.';
  }

}
