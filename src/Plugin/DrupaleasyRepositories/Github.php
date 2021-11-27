<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;
use Github\Client;
use Symfony\Component\HttpClient\HttplugClient;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Key\KeyRepositoryInterface;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("Github"),
 *   description = @Translation("Github.com")
 * )
 */
class Github extends DrupaleasyRepositoriesPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Github client.
   *
   * @var Github\Client
   */
  protected $client;

  /**
   * The Key repository service.
   *
   * @var Drupal\Key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KeyRepositoryInterface $key_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function authenticate() {
    $this->client = Client::createWithHttpClient(new HttplugClient());
    $github_key = $this->keyRepository->getKey('github')->getKeyValue();
    $this->client->authenticate('ultimike', $github_key, CLIENT::AUTH_CLIENT_ID);
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
