<?php

namespace Drupal\drupaleasy_repositories\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\drupaleasy_repositories\Event\UserLoginEvent;

/**
 * DrupalEasy Repositories event subscriber.
 */
class DrupaleasyRepositoriesSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * User login event handler.
   *
   * @param Drupal\drupaleasy_repositories\Event\UserLoginEvent $event
   *   User login event.
   */
  public function onUserLogin(UserLoginEvent $event) {
    $this->messenger->addStatus('hey now');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserLoginEvent::EVENT_NAME => ['onUserLogin'],
    ];
  }

}
