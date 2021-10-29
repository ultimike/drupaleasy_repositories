<?php

namespace Drupal\drupaleasy_repositories\Commands;

use Drush\Commands\DrushCommands;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DrupaleasyRepositoriesCommands extends DrushCommands {

  /**
   * The DrupalEasy repositories manager service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $repositoriesService;

  /**
   * The Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositories_service
   *   The DrupalEasyRepositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(DrupaleasyRepositoriesService $repositories_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->repositoriesService = $repositories_service;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * Command description here.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option uid
   *   The user ID of the user to update.
   * @usage der:update-repositories --uid=2
   *   Update a user repositories.
   *
   * @command der:update-repositories
   * @aliases der:ur
   */
  public function updateRepositories(array $options = ['uid' => NULL]) {
    if (!empty($options['uid'])) {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityManager->getStorage('user');

      $account = $user_storage->load($options['uid']);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->logger()->notice(dt('Repositories updated.'));
        }
      }
      else {
        $this->logger()->notice(dt('User doesn\'t exist.'));
      }
    }
    else {
      // @todo Implement checking of all users.
      $this->logger()->notice(dt('This functionality hasn\'t been implemented yet.'));
    }

  }

}
