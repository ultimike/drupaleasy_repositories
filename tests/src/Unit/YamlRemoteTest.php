<?php

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YamlRemote;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class YamlRemoteTest extends UnitTestCase {

  /**
   * The Yaml Remote plugin.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YamlRemote
   */
  protected $yamlRemote;

  /**
   * Drupal's messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the messenger object.
    $this->messenger = $this->getMockBuilder('\Drupal\Core\Messenger\MessengerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the key.repository object.
    $this->keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->yamlRemote = new YamlRemote([], '', [], $this->messenger, $this->keyRepository);
  }

  /**
   * Test that the help text returns as expected.
   *
   * @covers ::validateHelpText
   */
  public function testValidateHelpText() {
    self::assertEquals('https://anything.anything/anything/anything.yml (or "http")', $this->yamlRemote->validateHelpText(), 'Help text does not match.');
  }

  /**
   * Test that the URL validator works.
   *
   * @dataProvider validateProvider
   *
   * @covers ::validate
   */
  public function testValidate($testString, $expected) {
    self::assertEquals($expected, $this->yamlRemote->validate($testString));
  }

  /**
   * Data provider for testValidate().
   */
  public function validateProvider() {
    return [
      [
        'A test string',
        FALSE,
      ],
      [
        'http://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yaml',
        FALSE,
      ],
      [
        '/var/www/html/anything.yaml',
        FALSE,
      ],
    ];
  }

  /**
   * Test that a repo can be read properly.
   *
   * @covers ::getRepo
   */
  public function testGetRepo() {
    $repo = $this->yamlRemote->getRepo(__DIR__ . '/../../assets/batman-repo.yml');
    // getRepo() returns an array of repositories, in this case only one.
    $repo = reset($repo);
    self::assertEquals('yaml', $repo['source'], 'Source does not match.');
  }

  /**
   * Test that the source and uri can be set properly.
   *
   * @covers ::addSourceAndUri
   */
  public function gggtestAddSourceAndUri() {
    // Use reflection to make addSourceAndUri() public.
    $reflection_addSourceAndUri = new \ReflectionMethod($this->yamlRemote, 'addSourceAndUri');
    $reflection_addSourceAndUri->setAccessible(TRUE);
    $this->assertEquals(4, $reflection_addSourceAndUri->invokeArgs($controller, [2,2]));
  }

}
