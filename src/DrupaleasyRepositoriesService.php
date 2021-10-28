<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * This is the main class that calls all the enabled plugins.
 */
class DrupaleasyRepositoriesService {

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
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactory $config_factory) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
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
        \Drupal::messenger()->addMessage($repository->label());
      }
    }
    return TRUE;
  }

}
