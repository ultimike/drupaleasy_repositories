<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * This is the main class that calls all the enabled plugins.
 */
class DrupaleasyRepositoriesService {

  use StringTranslationTrait;

  /**
   * The plugin.manager.drupaleasy_repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager
   */
  protected $pluginManagerDrupaleasyRepositories;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account) {
    $repository_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_ids as $repository_id) {
      if (!empty($repository_id)) {
        /** @var DrupaleasyRepositoriesInterface $repository */
        $repository = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_id);
        // @todo Do something (state variable) to limit checking to once/day?
        \Drupal::messenger()->addMessage($this->t('Processing data from @repo.', ['@repo' => $repository->label()]));

        // Check if repositories exist for this user. If so, return count.
        if ($count = $repository->count($account)) {
          \Drupal::messenger()->addMessage($this->t('UID @uid has @count repositories here.', [
            '@uid' => $account->id(),
            '@count' => $count,
          ]));
        }
        else {
          \Drupal::messenger()->addMessage($this->t('UID @uid has no repositories here.', ['@uid' => $account->id()]));
          return FALSE;
        }

        // Get name and description of repositories.
        $repos_info = $repository->getInfo($account);
        foreach ($repos_info as $info) {
          \Drupal::messenger()->addMessage($this->t('Found repo @name (@desc)', [
            '@name' => $info['label'],
            '@desc' => $info['description'],
          ]));
        }

        // Gather existing repository nodes for this user.
        $query = $this->entityManager->getStorage('node')->getQuery();
        $query->condition('type', 'repository')
          ->condition('uid', $account->id());
        $results = $query->execute();
        $nodes = Node::loadMultiple($results);

        // Create/update/delete nodes for each repository.
        foreach ($repos_info as $key => $info) {
          $node = Node::create([
            'uid' => $account->id(),
            'type' => 'repository',
            'title' => $info['label'],
            'field_description' => $info['description'],
            'field_machine_name' => $key,
          ]);
          $node->save();
        }
      }
    }
    return TRUE;
  }

}
