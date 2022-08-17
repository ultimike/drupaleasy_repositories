<?php

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a repository is updated.
 */
class RepoUpdatedEvent extends Event {

  const EVENT_NAME = 'drupaleasy_repositories_repo_updated';

  /**
   * The node updated.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * The action performed on the node.
   *
   * @var string
   */
  public $action;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was updated.
   * @param string $action
   *   The action performed on the node.
   */
  public function __construct(NodeInterface $node, string $action) {
    $this->node = $node;
    $this->action = $action;
  }

}
