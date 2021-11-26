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
   * The Github client.
   *
   * @var Github\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected function authenticate() {
    $this->client = Client::createWithHttpClient(new HttplugClient());
    $this->client->authenticate('ultimike', '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', CLIENT::AUTH_CLIENT_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function count(UserInterface $user) {
    $this->authenticate();
    try {
      $repos = $this->client->api('user')->repositories($user->label());
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
    $this->authenticate();
    try {
      $repos = $this->client->api('user')->repositories($user->label());
    }
    catch (\Throwable $th) {
      return NULL;
    }

    foreach ($repos as $remote_repo) {
      $repositories[$remote_repo['full_name']] = [
        'label' => $remote_repo['name'],
        'description' => $remote_repo['description'],
        'num_open_issues' => $remote_repo['open_issues_count'],
        // This needs to be the same as the plugin ID.
        'source' => 'github',
        'url' => $remote_repo['html_url'],
      ];
    }
    return $repositories;
  }

}
