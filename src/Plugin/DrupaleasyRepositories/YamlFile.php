<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yaml",
 *   label = @Translation("Local .yaml file"),
 *   description = @Translation("Yaml file")
 * )
 */
class YamlFile extends DrupaleasyRepositoriesPluginBase {

  /**
   * The file_system service.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file_url_generator service.
   *
   * @var Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
  }

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
    $item['url'] = $this->fileUrlGenerator->generateAbsoluteString($uri);
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
    if (file_exists($uri)) {
      $repo_info = Yaml::decode(file_get_contents($uri));
      array_walk($repo_info, ['self', 'addSourceAndUri'], $uri);
      return $repo_info;
    }
    return NULL;
  }

}
