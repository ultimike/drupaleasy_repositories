<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface, ContainerFactoryPluginInterface {

  /**
   * The repository client used to make API calls.
   *
   * @var object
   */
  private $client;

  /**
   * Sets the $client.
   *
   * @param object $client
   *   The client object.
   */
  public function setClient(object $client) {
    $this->client = $client;
  }

  /**
   * Gets the $client.
   */
  public function getClient() {
    return($this->client);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidator() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText() {
    return '';
  }

  /**
   * Build array of a single repository.
   *
   * @param string $full_name
   *   The full name of the repository.
   * @param string $label
   *   The short name of the repository.
   * @param string $description
   *   The description of the repository.
   * @param int $num_open_issues
   *   The number of open issues.
   * @param string $source
   *   The source of the repository.
   * @param string $url
   *   The URI of the repository.
   *
   * @return array
   *   An array containing info about a single repository.
   */
  protected function mapToCommonFormat(string $full_name, string $label, string $description, int $num_open_issues, string $source, string $url) {
    $repo_info[$full_name] = [
      'label' => $label,
      'description' => $description,
      'num_open_issues' => $num_open_issues,
      // This needs to be the same as the plugin ID.
      'source' => $source,
      'url' => $url,
    ];
    return $repo_info;
  }

}
