<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Batch service class to integration with Batch API.
 */
class Batch {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The DrupalEasy repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $drupaleasyRepositoriesService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasy_repositories_service
   *   The DrupalEasy repositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(DrupaleasyRepositoriesService $drupaleasy_repositories_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->drupaleasyRepositoriesService = $drupaleasy_repositories_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Updates all user repositories using the Batch API.
   */
  public function updateAllUserRepositories() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', '1');
    $users = $query->execute();

    // Create a Batch API item for each user.
    foreach ($users as $key => $user) {
      $operations[] = ['drupaleasy_batch_operation',
        [$this, 'updateRepositoriesBatch', ['uid' => $key]],
      ];
    }
    $batch = [
      'operations' => $operations,
      'finished' => 'update_all_repositories_finished',
    ];
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Batch process callback from updating user repositories.
   *
   * @param array $vars
   *   Associative array that includes the UID to update.
   * @param object $context
   *   Context for operations.
   */
  public function updateRepositoriesBatch(array $vars, object &$context) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($vars['uid']);
    $this->drupaleasyRepositoriesService->updateRepositories($account);
    $context['results'][] = $vars['uid'];
    $context['results']['num']++;
    $context['message'] = $this->t('Updating repositories belonging to "@username".',
      ['@username' => $account->label()]
    );
  }

}
