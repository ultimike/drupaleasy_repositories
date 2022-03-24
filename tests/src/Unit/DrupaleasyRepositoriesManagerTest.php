<?php

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
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
   * The test Plugin manager mock.
   *
   * @var TestDrupaleasyRepositoriesPluginManager
   */
  protected $manager;

  /**
   * The messenger mock.
   *
   * @var \Drupal\Core]\Messenger\MessengerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $messenger;


  /**
   * The key.repository mock.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $keyRepository;

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
      'label' => 'Example plugin',
      'description' => 'Example plugin for DrupalEasy Repositories tests.',
      'dependencies' => [],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock a Discovery object to replace AnnotationClassDiscovery.
    $this->discovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->definitions));

    // Mock the messenger object.
    $this->messenger = $this->getMockBuilder('\Drupal\Core\Messenger\MessengerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the key.repository object.
    $this->keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a dummy container.
    $this->container = new ContainerBuilder();
    $this->container->set('messenger', $this->messenger);
    $this->container->set('key.repository', $this->keyRepository);
    \Drupal::setContainer($this->container);

    // Create a mock cache backend.
    $namespaces = new \ArrayObject(['Drupal\drupaleasy_repositories' => '/var/www/html/web/modules/custom/drupaleasy_repositories/src/Plugin/DrupaleasyRepositories']);
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    // Create a mock module handler.
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    // Create the test Plugin manager.
    $this->manager = new TestDrupaleasyRepositoriesPluginManager($namespaces, $cache_backend, $module_handler);
    $this->manager->setDiscovery($this->discovery);
  }

  /**
   * Test creating an instance of the DrupaleasyRepositoriesManager.
   */
  public function testCreateInstance() {
    $example_instance = $this->manager->createInstance('drupaleasy_repositories_example');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf('Drupal\drupaleasy_repositories_example\Plugin\DrupaleasyRepositories\ExamplePlugin', $example_instance);
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginBase', $example_instance);
    $this->assertArrayHasKey('url', $plugin_def);
    $this->assertTrue($plugin_def['url'] == 'drupaleasy_repositories_example');
  }

}

/**
 * Provides a test version of DevelGeneratePluginManager.
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
