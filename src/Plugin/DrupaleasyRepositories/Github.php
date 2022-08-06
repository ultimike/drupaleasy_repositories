<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\Client;
use Github\AuthMethod;
use Github\Exception\RuntimeException;
use Symfony\Component\HttpClient\HttplugClient;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("GitHub"),
 *   description = @Translation("Github.com")
 * )
 */
class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * Authenticate with Github.
   */
  protected function authenticate(): bool {
    $this->client = Client::createWithHttpClient(new HttplugClient());
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();
    try {
      // The authenticate() method does not return TRUE/FALSE, only an error if
      // unsuccessful.
      $this->client->authenticate($github_key['username'], $github_key['personal_access_token'], AuthMethod::CLIENT_ID);
      // Uncomment the next line to test authentication.
      //$this->client->currentUser()->emails()->allPublic();
    }
    catch (RuntimeException $th) {
      $this->messenger->addMessage($this->t('Github error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return FALSE;
    }
    catch (\Throwable $th) {
      $this->messenger->addMessage($this->t('Github error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return FALSE;
    }
    return TRUE;
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
  public function getRepo(string $uri): array {
    // Parse the URI.
    $all_parts = parse_url($uri);
    $parts = explode('/', $all_parts['path']);
    if ($this->authenticate()) {
      try {
        $repo = $this->client->api('repo')->show($parts[1], $parts[2]);
      }
      catch (RuntimeException $th) {
        $this->messenger->addMessage($this->t('Github error: @error', [
          '@error' => $th->getMessage(),
        ]));
        return [];
      }
      catch (\Throwable $th) {
        $this->messenger->addMessage($this->t('Github error: @error', [
          '@error' => $th->getMessage(),
        ]));
        return [];
      }
      return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues_count'], $repo['html_url']);
    }
    else {
      return [];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validate($uri): bool {
    $pattern = '/^(https:\/\/)github.com\/[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+/';

    if (preg_match($pattern, $uri) == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/vendor/name';
  }

}
