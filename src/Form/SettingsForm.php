<?php

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupaleasy_repositories_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drupaleasy_repositories.settings'];
  }


  /**
   * The DrupalEasy repositories manager service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager
   */
  protected $repositoriesManager;

  /**
   * Constructs an SettingsForm object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager
   *   The DrupalEasy repositories manager service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager) {
    $this->repositoriesManager = $drupaleasy_repositories_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.drupaleasy_repositories')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['example'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example'),
      '#default_value' => $this->config('drupaleasy_repositories.settings')->get('example'),
    ];

    $plugins = $this->repositoriesManager->getDefinitions();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('example') != 'example') {
      $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('drupaleasy_repositories.settings')
      ->set('example', $form_state->getValue('example'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
