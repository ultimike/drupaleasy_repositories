<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Drupal\user\UserInterface;
use Github\Client;
use Github\Exception\RuntimeException;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Key\KeyRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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

  use StringTranslationTrait;

  /**
   * The Github client.
   *
   * @var Github\Client
   */
  protected $client;

  /**
   * The Key repository service.
   *
   * @var \Drupal\Key\KeyRepositoryInterface
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
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();
    $this->client->authenticate($github_key['username'], $github_key['personal_access_token'], CLIENT::AUTH_CLIENT_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function count(UserInterface $user) {
    $repos = $this->getRepos($user);
    if ($repos) {
      return count($repos);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(UserInterface $user) {
    $repos = $this->getRepos($user);

    if (count($repos)) {
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
    return NULL;
  }

  /**
   * Gets repositories from Github.
   *
   * @param Drupal\user\UserInterface $user
   *   The user whose repositories to get.
   *
   * @return array
   *   The repositories.
   */
  protected function getRepos(UserInterface $user) {
    $this->authenticate();
    try {
      return $this->client->api('user')->repositories($user->label());
    }
    catch (RuntimeException $th) {
      \Drupal::messenger()->addMessage($this->t('Github error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return NULL;
    }
    catch (\Throwable $th) {
      return NULL;
    }
  }

  /**
   * Gets a single repository from Github.
   *
   * @param string $uri
   *   The URI of the repository to get.
   *
   * @return array
   *   The repositories.
   */
  public function getRepo(string $uri) {
    // Parse the URI.
    $all_parts = parse_url($uri);
    $parts = explode('/', $all_parts['path']);
    $this->authenticate();
    try {
      $repo = $this->client->api('repo')->show($parts[1], $parts[2]);
    }
    catch (RuntimeException $th) {
      \Drupal::messenger()->addMessage($this->t('Github error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return NULL;
    }
    catch (\Throwable $th) {
      return NULL;
    }
    return $this->mapToCommonFormat($repo);
  }

  /**
   * {@inheritdoc}
   */
  // Move this to the service and pass in data?
  protected function mapToCommonFormat(array $repo_raw) {
    $repo_info[$repo_raw['full_name']] = [
      'label' => $repo_raw['name'],
      'description' => $repo_raw['description'],
      'num_open_issues' => $repo_raw['open_issues_count'],
      // This needs to be the same as the plugin ID.
      'source' => 'github',
      'url' => $repo_raw['html_url'],
    ];
    return $repo_info;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidator() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($uri) {
    $pattern = '/^(https:\/\/)github.com\/[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+/';

    if (preg_match($pattern, $uri) == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText() {
    return 'https://github.com/vendor/name';
  }

}
