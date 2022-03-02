<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;

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
   * When set to "true", no nodes are created, updated, or deleted.
   *
   * @var bool
   */
  protected $dryRun;

  /**
   * Drupal's messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal's messenger service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, bool $dry_run, MessengerInterface $messenger) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_type_manager;
    $this->dryRun = $dry_run;
    $this->messenger = $messenger;
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
    $repos_info = [];
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        /** @var DrupaleasyRepositoriesInterface $repository_location */
        $repository_location = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
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
    }
    return $this->updateRepositoryNodes($repos_info, $account);
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
   * @param Drupal\user\UserInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, UserInterface $account) {
    if (!$repos_info) {
      return TRUE;
    }
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityManager->getStorage('node');

    foreach ($repos_info as $key => $info) {
      $this->messenger->addMessage($this->t('Found repo @name (@desc)', [
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
        ->condition('field_source', $info['source']);
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
      ->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
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
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user. This only validates non-yaml
   * repository URLs.
   *
   * @param array $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return array
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid) {
    $errors = [];
    $repository_services = [];

    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repository_services[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $repo_info = [];
          // Check to see if the URI if valid for any enabled plugins.
          /** @var DrupaleasyRepositoriesInterface $repository */
          foreach ($repository_services as $repository_service) {
            if ($repository_service->validate($uri)) {
              $repo_info = $repository_service->getRepo($uri);
              if (!$repo_info) {
                $errors[] = $this->t('The url %uri is not a valid url.', ['%uri' => $uri]);
              }
            }
          }

          // Check to see if repository was previously added by another user.
          if ($repo_info) {
            if (!$this->isUnique($repo_info, $uid)) {
              $errors[] = $this->t('The repository at %uri has been added by another user.', ['%uri' => $uri]);
            }
          }
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
    $repositories = [];
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories');

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repositories[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    $help = [];

    /** @var DrupaleasyRepositoriesInterface $repository */
    foreach ($repositories as $repository) {
      $help[] = $repository->validateHelpText();
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
  protected function isUnique(array $repo_info, int $uid) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityManager->getStorage('node');

    // Calculate hash value.
    $hash = md5(serialize(array_pop($repo_info)));

    // Look for repository nodes with a matching hash.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $uid, '<>')
      ->condition('field_hash', $hash);
    $results = $query->execute();

    if (count($results)) {
      return FALSE;
    };
    return TRUE;
  }

}
