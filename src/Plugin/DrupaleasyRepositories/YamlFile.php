<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Serialization\Yaml;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yaml",
 *   label = @Translation("Local .yaml file"),
 *   description = @Translation("Yaml file")
 * )
 */
class YamlFile extends DrupaleasyRepositoriesPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The file_system service.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  protected function authenticate(UserInterface $user) {
    // @todo get filename from user account field?
    $filename = $this->fileSystem->realpath('public://user/' . $user->id() . '/repo.yml');
    if (file_exists($filename) && is_readable(($filename))) {
      return $filename;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function count(UserInterface $user) {
    if ($filename = $this->authenticate($user)) {
      $repos = Yaml::decode(file_get_contents($filename));
      return count($repos);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(UserInterface $user) {
    if ($filename = $this->authenticate($user)) {
      $repos = Yaml::decode(file_get_contents($filename));
      return $repos;
    }
    return NULL;
  }

}
