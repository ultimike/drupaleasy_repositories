<?php

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class DrupaleasyRepositoriesServiceTest extends KernelTestBase {

  use RepositoryContentTypeTrait;

  /**
   * The test service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected $drupaleasyRepositoriesService;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'key',
    'node',
    'field',
    'user',
    'system',
    // For text_long field types.
    'text',
    // For link field types.
    'link',
  ];

  /**
   * Test repository info.
   *
   * @var array
   */
  protected $testRepoInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupaleasyRepositoriesService = $this->container->get('drupaleasy_repositories.service');

    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $this->moduleHandler = $this->container->get('module_handler');

    // Enable the .yml repository plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories', ['yml_remote' => 'yml_remote']);
    $config->save();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();
    $this->container->get('current_user')->setAccount($this->adminUser);

    $this->createRepositoryContentType();

    $batman_repo = $this->getBatmanRepo();
    $repo = reset($batman_repo);
    $node = Node::create([
      'type' => 'repository',
      'title' => $repo['label'],
      'field_machine_name' => array_key_first($batman_repo),
      'field_url' => $repo['url'],
      'field_hash' => '06b2ffbb0eece1fee308a6429c287c35',
      'field_number_of_issues' => $repo['num_open_issues'],
      'field_source' => $repo['source'],
      'field_description' => $repo['description'],
      'user_id' => $this->adminUser->id(),
    ]);
    $node->save();
  }

  /**
   * Data provider for testIsUnique().
   */
  public function provideTestIsUnique() {
    $unique_repo_info['superman-repo'] = [
      'label' => 'The Superman repository',
      'description' => 'This is where Superman keeps all his crime-fighting code.',
      'num_open_issues' => 0,
      'source' => 'yml',
      'url' => 'https://example.com/superman-repo.yml',
    ];
    return [
      [FALSE, $this->getBatmanRepo()],
      [TRUE, $unique_repo_info],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are unique.
   *
   * This test doesn't call the DrupaleasyRepositoriesService::getRepo()
   * method, so we can test with real URLs.
   *
   * @covers ::isUnique
   * @dataProvider provideTestIsUnique
   * @test
   */
  public function testIsUnique($expected, $repo) {
    // Use reflection to make isUnique() public.
    $reflection_is_unique = new \ReflectionMethod($this->drupaleasyRepositoriesService, 'isUnique');
    $reflection_is_unique->setAccessible(TRUE);
    $return = $reflection_is_unique->invokeArgs(
      $this->drupaleasyRepositoriesService,
      // Use $uid = 999 to ensure it is different from $this->adminUser.
      [$repo, 999]
    );
    $this->assertEquals($expected, $return);
  }

  /**
   * Data provider for testValidateRepositoryUrls().
   */
  public function provideValidateRepositoryUrls() {
    // This is run before setup() and other things so $this->container
    // isn't available here!
    return [
      ['', [['uri' => '/tests/assets/batman-repo.yml']]],
      ['is not valid', [['uri' => '/tests/assets/batman-repo.ym']]],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are valid.
   *
   * ...
   *
   * @covers ::validateRepositoryUrls
   * @dataProvider provideValidateRepositoryUrls
   * @test
   */
  public function testValidateRepositoryUrls($expected, $urls) {
    // Get the full path to the test .yml file.
    /** @var \Drupal\Core\Extension\Extension $module */
    $module = $this->moduleHandler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    foreach ($urls as $key => $url) {
      if (isset($url['uri'])) {
        $urls[$key]['uri'] = $module_full_path . $url['uri'];
      }
    }

    // Use reflection to make validateRepositoryUrls() public.
    $reflection_is_unique = new \ReflectionMethod($this->drupaleasyRepositoriesService, 'validateRepositoryUrls');
    $reflection_is_unique->setAccessible(TRUE);
    $return = $reflection_is_unique->invokeArgs(
      $this->drupaleasyRepositoriesService,
      // Use $uid = 999 to ensure it is different from $this->adminUser.
      [$urls, 999]
    );
    // Only check assertion if no error is expected nor returned.
    if (($expected != '') || ($return != $expected)) {
      $this->assertTrue((bool) mb_stristr($return, $expected));
    }
  }

}
