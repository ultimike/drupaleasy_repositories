<?php

namespace Drupal\drupaleasy_repositories\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\drupaleasy_repositories\Event\UserLoginEvent;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;

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
   * The DrupalEasy repositories manager service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $repositoriesService;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasy_repositories_service
   *   The DrupalEasy repository service.
   */
  public function __construct(MessengerInterface $messenger, DrupaleasyRepositoriesService $drupaleasy_repositories_service) {
    $this->messenger = $messenger;
    $this->repositoriesService = $drupaleasy_repositories_service;
  }

  /**
   * User login event handler.
   *
   * @param Drupal\drupaleasy_repositories\Event\UserLoginEvent $event
   *   User login event.
   */
  public function onUserLogin(UserLoginEvent $event) {
    if ($this->repositoriesService->updateRepositories($event->account)) {
      $this->messenger->addStatus('Repository nodes updated.');
    }
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
