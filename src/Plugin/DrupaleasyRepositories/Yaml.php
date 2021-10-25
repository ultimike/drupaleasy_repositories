<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yaml",
 *   label = @Translation("Local .yaml file"),
 *   description = @Translation("Yaml file")
 * )
 */
class Yaml extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function authenticate(UserInterface $user) {
    // @todo Ensure the file is readable.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfRepositories(UserInterface $user) {
    return rand(0, 10);
  }

}
