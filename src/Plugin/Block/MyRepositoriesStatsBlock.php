<?php

namespace Drupal\drupaleasy_repositories\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Provides a My repositories stats block.
 *
 * @Block(
 *   id = "drupaleasy_repositories_my_repositories_stats",
 *   admin_label = @Translation("My repositories stats"),
 *   category = @Translation("DrupalEasy")
 * )
 */
class MyRepositoriesStatsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   The plugin config.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        $this->t('Current user: @name', ['@name' => $this->currentUser->getAccountName()]),
        $this->t('Current timestamp: @timestamp', ['@timestamp' => $this->time->getCurrentTime()]),
        $this->t('Total number of comments in all repository nodes: @all', ['@all' => $this->calculateTotalComments()]),
        $this->t('Total number of comments in my repository nodes: @my', ['@my' => $this->calculateTotalComments($this->currentUser->id())]),
      ],
    ];

    $build['#cache'] = [
      'contexts' => ['user.roles', 'user'],
      'tags' => ['node_list:repository', 'drupaleasy_repositories'],
      'max-age' => Cache::PERMANENT,
      // Uncomment for BigPipe demo.
      //'max-age' => 0,
    ];

    return $build;
  }

  /**
   * Calculates the total number of comments for a user's repositories.
   *
   * @param int $uid
   *   An (optional) user to filter on.
   *
   * @return int
   *   The total number of comments.
   */
  protected function calculateTotalComments($uid = NULL): int {
    // Uncomment for BigPipe example.
    //usleep(3000000);

    $return = 0;
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('status', 1);
    if ($uid) {
      $query->condition('uid', $uid);
    }
    $results = $query->accessCheck(FALSE)->execute();

    foreach ($results as $nid) {
      $node = $node_storage->load($nid);
      if ($number_of_issues = $node->field_number_of_issues->value) {
        $return += $number_of_issues;
      }
    }

    return $return;
  }

}
