<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Session\AccountInterface;

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
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(AccountInterface $account) {
    return TRUE;
  }

}
