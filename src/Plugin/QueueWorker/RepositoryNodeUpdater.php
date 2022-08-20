<?php

namespace Drupal\drupaleasy_repositories\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines 'drupaleasy_repositories_repository_node_updater' queue worker.
 *
 * @QueueWorker(
 *   id = "drupaleasy_repositories_repository_node_updater",
 *   title = @Translation("Repository node updater"),
 *   cron = {"time" = 60}
 * )
 */
class RepositoryNodeUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The DrupalEasy repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $repositoriesService;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityManager;

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   The plugin config.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasy_repositories_service
   *   The DrupalEasy repositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, DrupaleasyRepositoriesService $drupaleasy_repositories_service, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->repositoriesService = $drupaleasy_repositories_service;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drupaleasy_repositories.service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data['uid'])) {
      $user_storage = $this->entityManager->getStorage('user');
      $account = $user_storage->load($data['uid']);
      $this->repositoriesService->updateRepositories($account);
    }
  }

}
