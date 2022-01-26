<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Key\KeyRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The repository client used to make API calls.
   *
   * @var object
   */
  private $client;

  /**
   * Drupal's messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * The Key repository service.
   *
   * @var \Drupal\Key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, KeyRepositoryInterface $key_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
    $this->keyRepository = $key_repository;
  }

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
