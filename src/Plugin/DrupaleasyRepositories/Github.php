<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;
use Github\Client;
use Symfony\Component\HttpClient\HttplugClient;

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
    $client = Client::createWithHttpClient(new HttplugClient());
    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function count(UserInterface $user) {
    $client = $this->authenticate($user);
    try {
      $repos = $client->api('user')->repositories($user->label());
    }
    catch (\Throwable $th) {
      return NULL;
    }
    return count($repos);
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(UserInterface $user) {
    $client = $this->authenticate($user);
    try {
      $repos = $client->api('user')->repositories($user->label());
    }
    catch (\Throwable $th) {
      return NULL;
    }

    foreach ($repos as $remote_repo) {
      $repositories[$remote_repo['full_name']] = [
        'label' => $remote_repo['name'],
        'description' => $remote_repo['description'],
      ];
    }
    return $repositories;
  }

}
