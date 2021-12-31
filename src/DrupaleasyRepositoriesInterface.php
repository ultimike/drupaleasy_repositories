<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\user\UserInterface;

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
   * Queries the site for the number of repositories belonging to the user.
   *
   * @param Drupal\user\UserInterface $user
   *   The user.
   *
   * @return int
   *   The number of repositories.
   */
  public function count(UserInterface $user);

  /**
   * Queries the repository source for info about each repository.
   *
   * @param Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array
   *   The name and description of each repository.
   */
  public function getInfo(UserInterface $user);

}
