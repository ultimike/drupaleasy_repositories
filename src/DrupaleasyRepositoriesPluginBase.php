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

}
