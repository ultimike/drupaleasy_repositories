<?php

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\user\UserInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired on user login.
 */
class UserLoginEvent extends Event {

  const EVENT_NAME = 'drupaleasy_repositories_user_login';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user logging in.
   */
  public function __construct(UserInterface $account) {
    $this->account = $account;
  }

}
