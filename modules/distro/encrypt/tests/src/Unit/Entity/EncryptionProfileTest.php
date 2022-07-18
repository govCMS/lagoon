<?php

namespace Drupal\Tests\encrypt\Unit\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\key\KeyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Unit tests for EncryptionProfile class.
 *
 * @ingroup encrypt
 *
 * @group encrypt
 *
 * @coversDefaultClass \Drupal\encrypt\Entity\EncryptionProfile
 */
class EncryptionProfileTest extends UnitTestCase {

  /**
   * A mocked Key entity.
   *
   * @var \Drupal\key\Entity\Key|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $key;

  /**
   * A mocked EncryptionMethod.
   *
   * @var \Drupal\encrypt\EncryptionMethodInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $encryptionMethod;

  /**
   * A mocked KeyRepository.
   *
   * @var \Drupal\key\KeyRepository|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyRepository;

  /**
   * A mocked plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Mock a Key entity.
    $this->key = $this->createMock('\Drupal\key\Entity\Key');

    // Set up expectations for key.
    $key_type = $this->createMock('\Drupal\key\Plugin\KeyType\EncryptionKeyType');
    $key_type->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('encryption'));
    $this->key->expects($this->any())
      ->method('getKeyType')
      ->will($this->returnValue($key_type));
    $this->key->expects($this->any())
      ->method('getKeyValue')
      ->will($this->returnValue("key_value"));

    // Mock an EncryptionMethod.
    $this->encryptionMethod = $this->createMock('\Drupal\encrypt\EncryptionMethodInterface');

    // Set up expectations for encryption method.
    $this->encryptionMethod->expects($this->any())
      ->method('checkDependencies')
      ->will($this->returnValue([]));

    // Mock a KeyRepository.
    $this->keyRepository = $this->createMock('\Drupal\key\KeyRepository');

    // Mock a plugin collection.
    $this->pluginCollection = $this->createMock('\Drupal\Core\Plugin\DefaultLazyPluginCollection');
  }

  /**
   * Tests the EncryptionProfile validate method.
   *
   * @covers ::__construct
   * @covers ::validate
   *
   * @dataProvider validateDataProvider
   */
  public function testValidate($enc_method_id, $enc_key, $enc_method_def, $expected_errors) {
    // Set up a mock for the EncryptionProfile class to mock some methods.
    $encryption_profile = $this->createMock('\Drupal\encrypt\Entity\EncryptionProfile');

    // Set expectations for the EncryptionMethod.
    $this->encryptionMethod->expects($this->any())
      ->method('getPluginDefinition')
      ->will($this->returnValue($enc_method_def));

    // Set expectations for EncryptionProfile entity.
    $encryption_profile->expects($this->any())
      ->method('getEncryptionMethodId')
      ->will($this->returnValue($enc_method_id));
    $encryption_profile->expects($this->any())
      ->method('getEncryptionKeyId')
      ->will($this->returnValue($enc_key));
    if ($enc_method_id == "test_encryption_method") {
      $encryption_profile->expects($this->any())
        ->method('getEncryptionMethod')
        ->will($this->returnValue($this->encryptionMethod));
    }
    else {
      $encryption_profile->expects($this->any())
        ->method('getEncryptionMethod')
        ->will($this->returnValue(FALSE));
    }
    if ($enc_key == "test_key") {
      $encryption_profile->expects($this->any())
        ->method('getEncryptionKey')
        ->will($this->returnValue($this->key));
    }
    if ($enc_key == "wrong_key") {
      $encryption_profile->expects($this->any())
        ->method('getEncryptionKey')
        ->will($this->returnValue(FALSE));
    }

    $errors = $encryption_profile->validate();
    $this->assertEquals($expected_errors, $errors);
  }

  /**
   * Data provider for validate() function.
   */
  public function validateDataProvider() {
    $valid_definition = [
      'id' => 'test_encryption_method',
      'title' => "Test encryption method",
      'key_type' => ['encryption'],
    ];

    $invalid_allowed_keytypes = $valid_definition;
    $invalid_allowed_keytypes['key_type'] = ['other_encryption'];

    return [
      'invalid_properties' => [
        NULL,
        NULL,
        NULL,
        ['No encryption method selected.', 'No encryption key selected.'],
      ],
      'invalid_encryption_method' => [
        'invalid_encryption_method',
        'test_key',
        NULL,
        ['The encryption method linked to this encryption profile does not exist.'],
      ],
      'invalid_key' => [
        'test_encryption_method',
        'wrong_key',
        $valid_definition,
        ['The key linked to this encryption profile does not exist.'],
      ],
      'invalid_keytypes' => [
        'test_encryption_method',
        'test_key',
        $invalid_allowed_keytypes,
        ['The selected key cannot be used with the selected encryption method.'],
      ],
      'normal' => [
        'test_encryption_method',
        'test_key',
        $valid_definition,
        [],
      ],
    ];
  }

  /**
   * Tests the getEncryptionMethod method.
   *
   * @covers ::getEncryptionMethod
   */
  public function testGetEncryptionMethod() {
    // Set up a mock for the EncryptionProfile class to mock some methods.
    $encryption_profile = $this->createMock('\Drupal\encrypt\Entity\EncryptionProfile');

    // Set up expectations for plugin collection.
    $this->pluginCollection->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_encryption_method')
      ->will($this->returnValue($this->encryptionMethod));

    // Set up expectations for encryption profile.
    $encryption_profile->expects($this->any())
      ->method('getPluginCollection')
      ->will($this->returnValue($this->pluginCollection));
    $encryption_profile->expects($this->any())
      ->method('getEncryptionMethodId')
      ->will($this->returnValue('test_encryption_method'));

    $result = $encryption_profile->getEncryptionMethod();
    $this->assertInstanceOf(EncryptionMethodInterface::class, $result);
  }

  /**
   * Tests the setEncryptionMethod method.
   *
   * @covers ::setEncryptionMethod
   */
  public function testSetEncryptionMethod() {
    // Set up a mock for the EncryptionProfile class to mock some methods.
    $encryption_profile = $this->createMock('\Drupal\encrypt\Entity\EncryptionProfile');

    $this->pluginCollection->expects($this->once())
      ->method('addInstanceID');

    // Set up expectations for encryption profile.
    $encryption_profile->expects($this->any())
      ->method('getPluginCollection')
      ->will($this->returnValue($this->pluginCollection));

    // Set up expectations for encryption method.
    $this->encryptionMethod->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('test_encryption_method'));

    $encryption_profile->setEncryptionMethod($this->encryptionMethod);
  }

  /**
   * Tests the getEncryptionKey method.
   *
   * @covers ::getEncryptionKey
   */
  public function testGetEncryptionKey() {
    // Set up a mock for the EncryptionProfile class to mock some methods.
    $encryption_profile = $this->createMock('\Drupal\encrypt\Entity\EncryptionProfile');

    $this->keyRepository->expects($this->any())
      ->method('getKey')
      ->with($this->equalTo('test_key'))
      ->will($this->returnValue($this->key));

    $encryption_profile->expects($this->any())
      ->method('getKeyRepository')
      ->will($this->returnValue($this->keyRepository));

    $encryption_profile->expects($this->any())
      ->method('getEncryptionKeyId')
      ->will($this->returnValue('test_key'));

    $result = $encryption_profile->getEncryptionKey();
    $this->assertInstanceOf(KeyInterface::class, $result);
  }

  /**
   * Tests the setEncryptionKey method.
   *
   * @covers ::setEncryptionKey
   */
  public function testSetEncryptionKey() {
    $encryption_profile = new EncryptionProfile([], 'encryption_profile');

    // Set up expectations for key entity.
    $this->key->expects($this->any())
      ->method('id')
      ->will($this->returnValue('test_key'));

    $encryption_profile->setEncryptionKey($this->key);
    $this->assertEquals("test_key", $encryption_profile->getEncryptionKeyId());
  }

}
