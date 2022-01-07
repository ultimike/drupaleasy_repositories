<?php

namespace Drupal\drupaleasy_repositories;

/**
 * Interface for drupaleasy_repositories plugins.
 */
interface DrupaleasyRepositoriesInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Returns TRUE if plugin has a field validator.
   *
   * @return bool
   *   TRUE if validator exists.
   */
  public function hasValidator();

  /**
   * URL validator.
   *
   * @param string $uri
   *   The URI to validate.
   *
   * @return bool
   *   Returns TRUE if the validation passes.
   */
  public function validate(string $uri);

  /**
   * Returns help text for the plugin's URL pattern required.
   *
   * @return string
   *   The help text string.
   */
  public function validateHelpText();

  /**
   * Queries the repository source for info about a repository.
   *
   * @param string $uri
   *   The URI of the repo.
   *
   * @return array
   *   The name and description of each repository.
   */
  public function getRepo(string $uri);

}
