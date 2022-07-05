<?php

namespace Drupal\Tests\key\Unit;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base class for key tests.
 */
abstract class KeyTestBase extends UnitTestCase {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Configuration storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $configStorage;

  /**
   * Entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Container builder.
   *
   * This should be used sparingly by test cases to add to the container as
   * necessary for tests.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mock the Config object, but methods will be mocked in the test class.
    $this->config = $this->getMockBuilder('\Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock ConfigEntityStorage object, but methods will be mocked in the test
    // class.
    $this->configStorage = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock EntityTypeManager service.
    $this->entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('key')
      ->willReturn($this->configStorage);

    // Create a dummy container.
    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $this->entityTypeManager);

    // Each test class should call \Drupal::setContainer() in its own setUp
    // method so that test classes can add mocked services to the container
    // without affecting other test classes.
  }

  /**
   * Return a token that could be a key.
   *
   * @return string
   *   A hashed string that could be confused as some secret token.
   */
  protected function createToken() {
    return strtoupper(hash('ripemd128', Crypt::hashBase64($this->getRandomGenerator()->string(30))));
  }

}
