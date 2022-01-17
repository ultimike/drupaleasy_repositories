<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase;
use Github\Client;
use Github\AuthMethod;
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
   * Authenticate with Github.
   */
  protected function authenticate() {
    $this->client = Client::createWithHttpClient(new HttplugClient());
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();
    try {
      $this->client->authenticate($github_key['username'], $github_key['personal_access_token'], AuthMethod::CLIENT_ID);
      return TRUE;
    }
    catch (RuntimeException $th) {
      \Drupal::messenger()->addMessage($this->t('Github error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return FALSE;
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
    if ($this->authenticate()) {
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
      return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues_count'], 'github', $repo['html_url']);
    }
    else {
      return NULL;
    }

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
