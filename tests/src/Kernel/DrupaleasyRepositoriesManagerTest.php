<?php

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

// use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
// use Drupal\Core\DependencyInjection\ContainerBuilder;
// use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesPluginManager
 * @group drupaleasy_repositories
 */
class DrupaleasyRepositoriesManagerTest extends KernelTestBase {

  /**
   * The plugin discovery.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * The test Plugin manager mock.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected $manager;

  /**
   * The messenger mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;


  /**
   * The key.repository mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'key',
  ];

  /**
   * A list of Drupaleasy Repositories plugin definitions.
   *
   * @var array
   */
  // protected $definitions = [
  //   'drupaleasy_repositories_example' => [
  //     'id' => 'drupaleasy_repositories_example',
  //     'class' => 'Drupal\drupaleasy_repositories_example\Plugin\DrupaleasyRepositories\ExamplePlugin',
  //     // @todo update this. Probably get rid of 'url' since it isn't part of the definition?
  //     'url' => 'drupaleasy_repositories_example',
  //     'label' => 'Example plugin',
  //     'description' => 'Example plugin for DrupalEasy Repositories tests.',
  //     'dependencies' => [],
  //   ],
  // ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // // Mock a Discovery object to replace AnnotationClassDiscovery.
    // $this->discovery = $this->createMock('\Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    // // For some reason PHP Intelephense flags "expects()".
    // $this->discovery->expects($this->any())
    //   ->method('getDefinitions')
    //   ->will($this->returnValue($this->definitions));

    // // Mock the messenger object.
    // $this->messenger = $this->getMockBuilder('\Drupal\Core\Messenger\MessengerInterface')
    //   ->disableOriginalConstructor()
    //   ->getMock();

    // // Mock the key.repository object.
    // $this->keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
    //   ->disableOriginalConstructor()
    //   ->getMock();

    // // Create a dummy container.
    // $this->container = new ContainerBuilder();
    // $this->container->set('messenger', $this->messenger);
    // $this->container->set('key.repository', $this->keyRepository);
    // \Drupal::setContainer($this->container);

    // // Create a mock cache backend.
    // $namespaces = new \ArrayObject(['Drupal\drupaleasy_repositories' => '/var/www/html/web/modules/custom/drupaleasy_repositories/src/Plugin/DrupaleasyRepositories']);
    // $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    // // Create a mock module handler.
    // $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    // // Create the test Plugin manager.
    // $this->manager = new DrupaleasyRepositoriesPluginManager($namespaces, $cache_backend, $module_handler);
    // $this->manager->setDiscovery($this->discovery);

    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Test creating an instance of the Yaml Remote plugin.
   */
  public function testYamlRemoteInstance() {
    $example_instance = $this->manager->createInstance('yaml_remote');
    $plugin_def = $example_instance->getPluginDefinition();
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YamlRemote', $example_instance);
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance);
    $this->assertArrayHasKey('label', $plugin_def);
    $this->assertTrue($plugin_def['label'] == 'Remote Yaml file');
  }

  /**
   * Test creating an instance of the Github plugin.
   */
  public function testGithubInstance() {
    $example_instance = $this->manager->createInstance('github');
    $plugin_def = $example_instance->getPluginDefinition();
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\Github', $example_instance);
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance);
    $this->assertArrayHasKey('label', $plugin_def);
    $this->assertTrue($plugin_def['label'] == 'Github');
  }

}

/**
 * Provides a test version of DevelGeneratePluginManager.
 */
// class TestDrupaleasyRepositoriesPluginManager extends DrupaleasyRepositoriesPluginManager {

//   /**
//    * Sets the discovery for the manager.
//    *
//    * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
//    *   The discovery object.
//    */
//   public function setDiscovery(DiscoveryInterface $discovery) {
//     $this->discovery = $discovery;
//   }

// }
