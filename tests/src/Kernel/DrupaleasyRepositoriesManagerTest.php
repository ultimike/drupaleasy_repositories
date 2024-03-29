<?php

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;

/**
 * @coversDefaultClass \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager
 * @group drupaleasy_repositories
 */
class DrupaleasyRepositoriesManagerTest extends KernelTestBase {

  /**
   * The test Plugin manager.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'key',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Test creating an instance of the .yml Remote plugin.
   *
   * @test
   */
  public function testYmlRemoteInstance(): void {
    $example_instance = $this->manager->createInstance('yml_remote');
    $plugin_def = $example_instance->getPluginDefinition();
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote', $example_instance, 'Plugin type does not match.');
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance, 'Plugin parent class type does not match.');
    $this->assertArrayHasKey('label', $plugin_def, 'The "Label" array key does not exist.');
    $this->assertTrue($plugin_def['label'] == 'Remote .yml file', 'The "Label" array value does not match.');
  }

  /**
   * Test creating an instance of the Github plugin.
   *
   * @test
   */
  public function testGithubInstance() {
    $example_instance = $this->manager->createInstance('github');
    $plugin_def = $example_instance->getPluginDefinition();
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\Github', $example_instance, 'Plugin type does not match.');
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance, 'Plugin parent class type does not match.');
    $this->assertArrayHasKey('label', $plugin_def, 'The "Label" array key does not exist.');
    $this->assertTrue($plugin_def['label'] == 'GitHub', 'The "Label" array value does not match.');
  }

}
