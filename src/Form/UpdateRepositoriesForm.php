<?php

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\Batch;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use cache.
 */
final class UpdateRepositoriesForm extends FormBase {

  /**
   * The DrupalEasy repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $repositoriesService;

  /**
   * The DrupalEasy repositories batch service.
   *
   * @var \Drupal\drupaleasy_repositories\Batch
   */
  protected $batch;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(DrupaleasyRepositoriesService $repositories_service, Batch $batch, EntityTypeManagerInterface $entity_type_manager) {
    $this->repositoriesService = $repositories_service;
    $this->batch = $batch;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('drupaleasy_repositories.batch'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_repositories';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Leave blank to update all repository nodes for all users.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Go',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($uid = $form_state->getValue('uid')) {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityManager->getStorage('user');

      $account = $user_storage->load($uid);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->messenger()->addMessage($this->t('Repositories updated.'));
        }
      }
      else {
        $this->messenger()->addMessage($this->t('User does not exist.'));
      }
    }
    else {
      $this->batch->updateAllUserRepositories();
    }

  }

}
