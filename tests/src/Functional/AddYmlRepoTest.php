<?php

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class AddYmlRepoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupaleasy_repositories', 'node', 'link', 'key'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories', ['yaml_remote' => 'yaml_remote']);
    $config->save();

    // Start the browsing session.
    $session = $this->assertSession();

    // Create and login as a Drupal admin user with permission to access
    // the DrupalEasy Repositories Settings page.
    $admin_user = $this->drupalCreateUser(['drupaleasy repositories configure']);
    $this->drupalLogin($admin_user);

    // Navigate to the DrupalEasy Repositories Settings page and confirm we
    // can reach it.
    $this->drupalGet('/admin/config/services/repositories');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);

    // Select the "Remote Yaml file" checkbox and submit the form.
    // @todo why doesn't this save to config?
    $edit = [
      'edit-repositories-yaml-remote' => 'yaml_remote',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->responseContains('The configuration options have been saved.');
    $session->checkboxChecked('edit-repositories-yaml-remote');
    $session->checkboxNotChecked('edit-repositories-github');

    // Create content type for repository nodes.
    // @todo Document that this requires the 'node' module.
    // drupalCreateContentType is an alias for createContentType
    $this->contentType = $this->createContentType(['type' => 'repository']);

    // Create Description field.
    FieldStorageConfig::create([
      'field_name' => 'field_description',
      'type' => 'text_long',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Description',
    ])->save();

    // Create Hash field.
    FieldStorageConfig::create([
      'field_name' => 'field_hash',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_hash',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Hash',
    ])->save();

    // Create Machine name field.
    FieldStorageConfig::create([
      'field_name' => 'field_machine_name',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_machine_name',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Machine name',
    ])->save();

    // Create Number of open issues field.
    FieldStorageConfig::create([
      'field_name' => 'field_number_of_issues',
      'type' => 'integer',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_number_of_issues',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Number of open issues',
    ])->save();

    // Create Source field.
    FieldStorageConfig::create([
      'field_name' => 'field_source',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Source',
    ])->save();

    // Create URL field.
    // @todo Document that this requires the link module as part of this test.
    FieldStorageConfig::create([
      'field_name' => 'field_url',
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_url',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'URL',
    ])->save();

    // Create multivalued Repositories URL field for user profiles.
    // @todo Document that this requires the link module as part of this test.
    FieldStorageConfig::create([
      'field_name' => 'field_repository_url',
      'type' => 'link',
      'entity_type' => 'user',
      // @todo Document that cardinality = -1 is unlimited (multivalued).
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_repository_url',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Repository URL',
    ])->save();
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository  */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Test that a yml repo can be added to profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and
   * that a repository node is successfully created upon saving the profile.
   */
  public function testAddYmlRepo() {
    // Create and login as a Drupal admin user with permission to access
    // the DrupalEasy Repositories Settings page.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to their edit profile page and confirm we can reach it.
    $this->drupalGet('/user/' . $user->id() . '/edit');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);

    // Get the full path to the test .yml file.
    $module_handler = \Drupal::service('module_handler');
    $module_full_path = \Drupal::request()->getUri() . $module_handler->getModule('drupaleasy_repositories')->getPath();

    // Add the test .yml file path and submit the form.
    // @todo update path to yml file - put in test directory.
    $edit = [
      'edit-field-repository-url-0-uri' => $module_full_path . '/tests/assets/batman-repo.yml',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->responseContains('The changes have been saved.');
    // @todo Document that we can check for the followimg message unless we also
    // enable the drupaleasy_notify module (which we don't want to do).
    //$session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been created');

    // Find the new repository node.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository')->accessCheck(FALSE);
    $results = $query->execute();
    $session->assert(count($results) == 1, 'One repository node was found.');

    // @todo check repository node values.
  }

  /**
   * Test callback.
   */
  // public function testSomething() {
  //   $admin_user = $this->drupalCreateUser(['access administration pages']);
  //   $this->drupalLogin($admin_user);
  //   $this->drupalGet('admin');
  //   $this->assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
  // }

}
