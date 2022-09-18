<?php

namespace Drupal\drupaleasy_repositories\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Cache\Cache;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      // Why does user name not work with user.roles cache context?
//      '#markup' => $this->t('Hello, @name, the total number of comments: %number.',
//        [
//          '@name' => $this->currentUser->getAccountName(),
//          '%number' => $this->calculateTotalComments(),
//        ]
//      ),
      '#markup' => $this->t('Random number: @random_number, the total number of comments: %number.',
        [
          // Change to date with seconds.
          '@random_number' => random_int(1, 9999),
          '%number' => $this->calculateTotalComments(),
        ]
      ),
    ];

    $build['#cache'] = [
      'contexts' => ['user.roles'],
      'tags' => ['node_list:repository', 'drupaleasy_repositories'],
      'max-age' => Cache::PERMANENT,
      // Uncomment for BigPipe example.
      //'max-age' => 0,
    ];

    return $build;
  }

  /**
   * Calculates the total number of comments for a user's repositories.
   *
   * @return int
   *   The total number of comments.
   */
  protected function calculateTotalComments(): int {
    // Uncomment for BigPipe example.
    //usleep(3000000);

    $return = 0;
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('status', 1);
//    if ($uid) {
//      $query->condition('uid', $this->currentUser->id())
//    }
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
