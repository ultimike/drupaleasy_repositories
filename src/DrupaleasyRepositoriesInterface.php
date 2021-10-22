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
   * Queries the site for the number of repositories belonging to the user.
   *
   * @param Drupal\user\UserInterface $user
   *   The user.
   *
   * @return int
   *   The number of repositories.
   */
  public function getNumberOfRepositories(UserInterface $user);

  /**
   * Authenticates the user on the site.
   *
   * @param Drupal\user\UserInterface $user
   *   The user.
   *
   * @return class
   *   The authenticated client object.
   */
  public function authenticate(UserInterface $user);

}
