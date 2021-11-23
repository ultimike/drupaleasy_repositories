<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Batch service class to integration with Batch API.
 */
class Batch {

  use StringTranslationTrait;

  /**
   * The DrupalEasy repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $repositoriesService;

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
    $this->repositoriesService = $drupaleasy_repositories_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Updates all user repositories using the Batch API.
   */
  public function updateAllUserRepositories(bool $drush = FALSE) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', '1');
    $users = $query->execute();

    // Create a Batch API item for each user.
    foreach ($users as $uid => $user) {
      $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    }
    $batch = [
      'operations' => $operations,
      'finished' => 'drupaleasy_update_all_repositories_finished',
      'file' => drupal_get_path('module', 'drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
    ];
    batch_set($batch);
    if ($drush) {
      drush_backend_batch_process();
    }
  }

  /**
   * Batch process callback from updating user repositories.
   *
   * @param int $uid
   *   User ID to update.
   * @param array|\ArrayAccess $context
   *   Context for operations. We do not want to typehit this as an array or
   *   an object as sometimes it is an array (when calling from a form) and
   *   sometimes it is an object (when calling from Drush).
   */
  public function updateRepositoriesBatch($uid, &$context) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($uid);
    $this->repositoriesService->updateRepositories($account);
    $context['results'][] = $uid;
    $context['results']['num']++;
    $context['message'] = $this->t('Updating repositories belonging to "@username".',
      ['@username' => $account->label()]
    );
  }

}
