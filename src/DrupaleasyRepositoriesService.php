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
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        /** @var DrupaleasyRepositoriesInterface $repository */
        $repository_location = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
        // @todo Do something (state variable) to limit checking to once/day?
        \Drupal::messenger()->addMessage($this->t('Processing data from @repo.', ['@repo' => $repository_location->label()]));

        // Check if repositories exist at this location for this user. If so,
        // return count.
        if ($count = $repository_location->count($account)) {
          \Drupal::messenger()->addMessage($this->t('UID @uid has @count repositories here.', [
            '@uid' => $account->id(),
            '@count' => $count,
          ]));
        }
        else {
          \Drupal::messenger()->addMessage($this->t('UID @uid has no repositories here.', ['@uid' => $account->id()]));
          return FALSE;
        }

        // Get name and description of each repository at this location.
        $repos_info = $repository_location->getInfo($account);

        $this->updateRepositoryNodes($repos_info, $account, $repository_location_id);

      }
    }
    // @todo Do something better with this.
    return TRUE;
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
   * @param Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account, string $repository_location_id) {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityManager->getStorage('node');

    foreach ($repos_info as $key => $info) {
      \Drupal::messenger()->addMessage($this->t('Found repo @name (@desc)', [
        '@name' => $info['label'],
        '@desc' => $info['description'],
      ]));

      // Calculate hash value.
      $hash = md5(serialize($info));

      // Look for repository nodes from this user with matching
      // machine_name.
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $repository_location_id);
      $results = $query->execute();

      if ($results) {
        $node = Node::load(reset($results));
        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle = $info['label'];
          $node->set('field_description', $info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $info['num_open_issues']);
          $node->set('field_source', $info['source']);
          $node->set('field_url', $info['url']);
          $node->set('field_hash', $hash);
          $node->save();
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        $node = Node::create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $info['label'],
          'field_description' => $info['description'],
          'field_machine_name' => $key,
          'field_number_of_issues' => $info['num_open_issues'],
          'field_source' => $info['source'],
          'field_url' => $info['url'],
          'field_hash' => $hash,
        ]);
        $node->save();
      }
    }

    // Remove repository nodes deleted from the source.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->condition('field_machine_name', array_keys($repos_info), 'NOT IN')
      ->condition('field_source', $repository_location_id);
    $results = $query->execute();
    if ($results) {
      $nodes = Node::loadMultiple($results);
      foreach ($nodes as $node) {
        $node->delete();
      }
    }
    return TRUE;
  }

}
