<?php

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager
 * @group drupaleasy_repositories
 */
class DrupaleasyRepositoriesManagerTest extends UnitTestCase {

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discovery;

  /**
   * A list of Drupaleasy Repositories plugin definitions.
   *
   * @var array
   */
  protected $definitions = [
    'drupaleasy_repositories_example' => [
      'id' => 'drupaleasy_repositories_example',
      'class' => 'Drupal\drupaleasy_repositories_example\Plugin\DrupaleasyRepositories\ExamplePlugin',
      // @todo update this?
      'url' => 'drupaleasy_repositories_example',
      'dependencies' => [],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Mock a Discovery object to replace AnnotationClassDiscovery.
    $this->discovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->definitions));
  }

  /**
   * Test creating an instance of the DrupaleasyRepositoriesManager.
   */
  public function testCreateInstance() {
    $namespaces = new \ArrayObject(['Drupal\drupaleasy_repositories' => '/var/www/html/web/modules/custom/drupaleasy_repositories/src/Plugin/DrupaleasyRepositories']);
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $manager = new TestDrupaleasyRepositoriesPluginManager($namespaces, $cache_backend, $module_handler);
    $manager->setDiscovery($this->discovery);

    $example_instance = $manager->createInstance('drupaleasy_repositories_example');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf('Drupal\drupaleasy_repositories_example\Plugin\DrupaleasyRepositories\ExamplePlugin', $example_instance);
    $this->assertArrayHasKey('url', $plugin_def);
    $this->assertTrue($plugin_def['url'] == 'drupaleasy_repositories_example');
  }

}

/**
 * Provides a testing version of DevelGeneratePluginManager with an empty constructor.
 */
class TestDrupaleasyRepositoriesPluginManager extends DrupaleasyRepositoriesPluginManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

}
