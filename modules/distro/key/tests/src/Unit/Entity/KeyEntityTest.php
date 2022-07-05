<?php

namespace Drupal\Tests\key\Unit\Entity;

use Drupal\key\Entity\Key;
use Drupal\key\Plugin\KeyProvider\ConfigKeyProvider;
use Drupal\key\Plugin\KeyType\AuthenticationKeyType;
use Drupal\key\Plugin\KeyInput\NoneKeyInput;
use Drupal\Tests\key\Unit\KeyTestBase;

/**
 * @coversDefaultClass \Drupal\key\Entity\Key
 * @group key
 */
class KeyEntityTest extends KeyTestBase {

  /**
   * Key type manager.
   *
   * @var \Drupal\key\Plugin\KeyPluginManager
   */
  protected $keyTypeManager;

  /**
   * Key provider manager.
   *
   * @var \Drupal\key\Plugin\KeyPluginManager
   */
  protected $keyProviderManager;

  /**
   * Key plugin manager.
   *
   * @var \Drupal\key\Plugin\KeyPluginManager
   */
  protected $keyInputManager;

  /**
   * Key type settings.
   *
   * @var array
   *   Key type settings to use for Authentication key type.
   */
  protected $key_type_settings;

  /**
   * Key provider settings.
   *
   * @var array
   *   Key provider settings to use for Configuration key provider.
   */
  protected $key_provider_settings;

  /**
   * Key input settings.
   *
   * @var array
   *   Key input settings to use for None key input.
   */
  protected $key_input_settings;

  /**
   * Assert that key entity getters work.
   */
  public function testGetters() {
    // Create a key entity using Configuration key provider.
    $values = [
      'key_id' => $this->getRandomGenerator()->word(15),
      'key_provider' => 'config',
      'key_provider_settings' => $this->key_provider_settings,
    ];
    $key = new Key($values, 'key');

    $this->assertEquals($values['key_provider'], $key->getKeyProvider()->getPluginId());
    $this->assertEquals($values['key_provider_settings'], $key->getKeyProvider()->getConfiguration());
    $this->assertEquals($values['key_provider_settings']['key_value'], $key->getKeyProvider()->getConfiguration()['key_value']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $definition = [
      'id' => 'authentication',
      'label' => 'Authentication',
    ];
    $this->key_type_settings = [];
    $plugin = new AuthenticationKeyType($this->key_type_settings, 'authentication', $definition);

    // Mock the KeyTypeManager service.
    $this->keyTypeManager = $this->getMockBuilder('\Drupal\key\Plugin\KeyPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->keyTypeManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([
        ['id' => 'authentication', 'label' => 'Authentication'],
      ]);
    $this->keyTypeManager->expects($this->any())
      ->method('createInstance')
      ->with('authentication', $this->key_type_settings)
      ->willReturn($plugin);
    $this->container->set('plugin.manager.key.key_type', $this->keyTypeManager);

    $definition = [
      'id' => 'config',
      'label' => 'Configuration',
      'storage_method' => 'config',
    ];
    $this->key_provider_settings = ['key_value' => $this->createToken(), 'base64_encoded' => FALSE];
    $plugin = new ConfigKeyProvider($this->key_provider_settings, 'config', $definition);

    // Mock the KeyProviderManager service.
    $this->keyProviderManager = $this->getMockBuilder('\Drupal\key\Plugin\KeyPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->keyProviderManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([
        ['id' => 'file', 'label' => 'File', 'storage_method' => 'file'],
        [
          'id' => 'config',
          'label' => 'Configuration',
          'storage_method' => 'config',
        ],
      ]);
    $this->keyProviderManager->expects($this->any())
      ->method('createInstance')
      ->with('config', $this->key_provider_settings)
      ->willReturn($plugin);
    $this->container->set('plugin.manager.key.key_provider', $this->keyProviderManager);

    $definition = [
      'id' => 'none',
      'label' => 'None',
    ];
    $this->key_input_settings = [];
    $plugin = new NoneKeyInput($this->key_input_settings, 'none', $definition);

    // Mock the KeyInputManager service.
    $this->keyInputManager = $this->getMockBuilder('\Drupal\key\Plugin\KeyPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->keyInputManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([
        ['id' => 'none', 'label' => 'None'],
      ]);
    $this->keyInputManager->expects($this->any())
      ->method('createInstance')
      ->with('none', $this->key_input_settings)
      ->willReturn($plugin);
    $this->container->set('plugin.manager.key.key_input', $this->keyInputManager);

    \Drupal::setContainer($this->container);
  }

}
