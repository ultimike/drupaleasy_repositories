<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\user\UserInterface;
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
   * The dry-run parameter.
   *
   * When set to "true", no nodes are creaated, updated, or deleted.
   *
   * @var bool
   */
  protected $dryRun;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   * @param bool $dry_run
   *   The dry_run parameter.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, bool $dry_run) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_type_manager;
    $this->dryRun = $dry_run;
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(UserInterface $account) {
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repos_info = [];
        /** @var DrupaleasyRepositoriesInterface $repository_location */
        $repository_location = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
        // @todo Do something (state variable) to limit checking to once/day?
        if ($repository_location->hasValidator()) {
          // Loop through repository URLs.
          foreach ($account->field_repository_url as $url) {
            // Check if URL validates for this repository.
            if ($repository_location->validate($url->uri)) {
              // Confirm repository exists.
              if ($repo_info = $repository_location->getRepo($url->uri)) {
                $repos_info += $repo_info;
              }
            }
          }
        }

        //\Drupal::messenger()->addMessage($this->t('Processing data from @repo.', ['@repo' => $repository_location->label()]));

        // Check if repositories exist at this location for this user. If so,
        // return count.
        // if ($count = $repository_location->count($account)) {
        //   \Drupal::messenger()->addMessage($this->t('UID @uid has @count repositories here.', [
        //     '@uid' => $account->id(),
        //     '@count' => $count,
        //   ]));
        // }
        // else {
        //   \Drupal::messenger()->addMessage($this->t('UID @uid has no repositories here.', ['@uid' => $account->id()]));
        //   return FALSE;
        // }

        // Get name and description of each repository at this location.
        //$repos_info = $repository_location->getInfo($account);

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
   * @param Drupal\user\UserInterface $account
   *   The user account whose repositories to update.
   * @param string $repository_location_id
   *   The repository location ID.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, UserInterface $account, string $repository_location_id) {
    if (!$repos_info) {
      return TRUE;
    }
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
          if (!$this->dryRun) {
            $node->save();
          }
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
        if (!$this->dryRun) {
          $node->save();
        }
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
        if (!$this->dryRun) {
          $node->delete();
        }
      }
    }
    return TRUE;
  }

  /**
   * Validate repository URLs.
   *
   * @param array $urls
   *   The urls to be validated.
   *
   * @return array
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls) {
    $errors = [];

    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repositories[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    foreach ($urls as $url) {
      if ($uri = trim($url['uri'])) {
        $validUrl = FALSE;
        /** @var DrupaleasyRepositoriesInterface $repository */
        foreach ($repositories as $repository) {
          if ($repository->hasValidator()) {
            if ($repository->validate($uri)) {
              $validUrl = TRUE;
            }
          }
        }
        if (!$validUrl) {
          $errors[] = $this->t('The url %uri is not a valid url.', ['%uri' => $uri]);
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    return NULL;
  }

  /**
   * Get valid repository URL help text from each plugin.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText() {
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repositories[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    $help = [];

    /** @var DrupaleasyRepositoriesInterface $repository */
    foreach ($repositories as $repository) {
      if ($repository->hasValidator()) {
        $help[] = $repository->validateHelpText();
      }
    }

    if (count($help)) {
      return implode(' ', $help);
    }

    return '';
  }

}
