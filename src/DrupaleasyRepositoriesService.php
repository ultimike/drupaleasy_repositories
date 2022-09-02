<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * This is the main class that calls all the enabled plugins.
 */
class DrupaleasyRepositoriesService {

  use StringTranslationTrait;

  /**
   * The plugin manager interface.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected PluginManagerInterface $pluginManagerDrupaleasyRepositories;

  /**
   * The configuration factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The dry-run parameter.
   *
   * When set to "true", no nodes are created, updated, or deleted.
   *
   * @var bool
   */
  protected bool $dryRun = FALSE;

  /**
   * Drupal's event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected ContainerAwareEventDispatcher $eventDispatcher;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager_drupaleasy_repositories
   *   The plugin manager interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   * @param bool $dry_run
   *   The dry_run parameter that specifies whether to save node changes.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   Drupal's event dispatcher service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(PluginManagerInterface $plugin_manager_drupaleasy_repositories, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, bool $dry_run, ContainerAwareEventDispatcher $event_dispatcher, CacheBackendInterface $cache, TimeInterface $time) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->dryRun = $dry_run;
    $this->eventDispatcher = $event_dispatcher;
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account, $bypass_cache = FALSE): bool {
    $repos_metadata = [];

    $cid = 'drupaleasy_repositories:repositories:' . $account->id();
    $cache = $this->cache->get($cid);
    if ($cache && !$bypass_cache) {
      $repos_metadata = $cache->data;
    }
    else {

      // Use Null Coalesce Operator in case no repositories are enabled.
      // See https://wiki.php.net/rfc/isset_ternary
      $enabled_repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];

      foreach ($enabled_repository_plugin_ids as $enabled_repository_plugin_id) {
        if (!empty($enabled_repository_plugin_id)) {
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_location */
          $repository_location = $this->pluginManagerDrupaleasyRepositories->createInstance($enabled_repository_plugin_id);
          // Loop through repository URLs.
          foreach ($account->field_repository_url ?? [] as $url) {
            // Check if the URL validates for this repository.
            if ($repository_location->validate($url->uri)) {
              // Confirm the repository exists and get metadata.
              if ($repo_metadata = $repository_location->getRepo($url->uri)) {
                $repos_metadata += $repo_metadata;
              }
            }
          }
        }
      }
      // Set cache.
      $this->cache->set($cid, $repos_metadata, $this->time->getRequestTime() + (60));
    }
    return $this->updateRepositoryNodes($repos_metadata, $account) &&
      $this->deleteRepositoryNodes($repos_metadata, $account);
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    if (!$repos_info) {
      return TRUE;
    }
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($repos_info as $key => $info) {
      // Calculate hash value.
      $hash = md5(serialize($info));

      // Look for repository nodes from this user with matching
      // machine_name.
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $info['source'])
        ->accessCheck(FALSE);
      $results = $query->execute();

      if ($results) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = $node_storage->load(reset($results));

        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle($info['label']);
          $node->set('field_description', $info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $info['num_open_issues']);
          $node->set('field_source', $info['source']);
          $node->set('field_url', $info['url']);
          $node->set('field_hash', $hash);
          if (!$this->dryRun) {
            $node->save();
            $this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        /** @var \Drupal\node\Entity\Node $node */
        $node = $node_storage->create([
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
        if (!$this->dryRun) {
          $node->save();
          $this->repoUpdated($node, 'created');
        }
      }
    }

    return TRUE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE);
    // We can't chain this above because $repos_info might be empty.
    if ($repos_info) {
      $query->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
    }
    $results = $query->execute();
    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          $this->repoUpdated($node, 'deleted');
        }
      }
    }
    return TRUE;
  }

  /**
   * Validate repository URLs.
   *
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user. This only validates non-yml
   * repository URLs.
   *
   * @param array $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return string
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid): string {
    $errors = [];
    $repository_plugins = [];

    // Get IDs of enabled DrupaleasyRepository plugins.
    $enabled_repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];
    if (!$enabled_repository_plugin_ids) {
      return 'There are no enabled repository plugins';
    }

    // Instantiate each enabled DrupaleasyRepository plugin.
    foreach ($enabled_repository_plugin_ids as $enabled_repository_plugin_id) {
      if (!empty($enabled_repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($enabled_repository_plugin_id);
      }
    }

    // Loop around each Repository URL and attempt to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $validated = FALSE;
          // Check to see if the URI is valid for any enabled plugins.
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
          foreach ($repository_plugins as $repository_plugin) {
            if ($repository_plugin->validate($uri)) {
              $validated = TRUE;
              $repo_metadata = $repository_plugin->getRepo($uri);
              if ($repo_metadata) {
                if (!$this->isUnique($repo_metadata, $uid)) {
                  $errors[] = $this->t('The repository at %uri has been added by another user.', ['%uri' => $uri]);
                }
              }
              else {
                $errors[] = $this->t('The repository at the url %uri was not found.', ['%uri' => $uri]);
              }
            }
          }
          if (!$validated) {
            $errors[] = $this->t('The repository url %uri is not valid.', ['%uri' => $uri]);
          }
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    // No errors found.
    return '';
  }

  /**
   * Get repository URL help text from each enabled plugin.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    $repository_plugins = [];
    $enabled_repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];

    foreach ($enabled_repository_plugin_ids as $enabled_repository_plugin_id) {
      if (!empty($enabled_repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($enabled_repository_plugin_id);
      }
    }

    $help = [];

    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
    foreach ($repository_plugins as $repository_plugin) {
      $help[] = $repository_plugin->validateHelpText();
    }

    if (count($help)) {
      return implode(' ', $help);
    }

    return '';
  }

  /**
   * Check to see if the repository is unique.
   *
   * @param array $repo_info
   *   The repository info.
   * @param int $uid
   *   The user ID of the submitter.
   *
   * @return bool
   *   Return true if the repository is unique.
   */
  protected function isUnique(array $repo_info, int $uid): bool {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $repo_metadata = array_pop($repo_info);

    // Look for repository nodes with a matching url.
    $query = $node_storage->getQuery();
    $results = $query->condition('type', 'repository')
      ->condition('field_url', $repo_metadata['url'])
      ->condition('uid', $uid, '<>')
      ->accessCheck(FALSE)
      ->execute();

    if (count($results)) {
      return FALSE;
    };
    return TRUE;
  }

  /**
   * Perform tasks when a repository is created, updated, or deleted.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node that was updated.
   * @param string $action
   *   The action that was performed on the node: updated, created, or deleted.
   */
  protected function repoUpdated(Node $node, string $action) {
    $event = new RepoUpdatedEvent($node, $action);
    $this->eventDispatcher->dispatch($event, RepoUpdatedEvent::REPO_UPDATED);
  }

}
