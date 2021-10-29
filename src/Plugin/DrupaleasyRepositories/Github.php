<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("Github"),
 *   description = @Translation("Github.com")
 * )
 */
class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function authenticate(UserInterface $user) {
    $client = $this->getClient();
    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function count(UserInterface $user) {
    // From https://github.com/KnpLabs/php-github-api/blob/master/doc/repos.md#get-the-repositories-of-a-specific-user
    // $repos = $client->api('user')->repositories('KnpLabs');
    // Returns a list of repos associated with a user.
    return rand(0, 10);
  }

}
