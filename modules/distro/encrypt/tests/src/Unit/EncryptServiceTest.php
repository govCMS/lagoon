<?php

namespace Drupal\Tests\encrypt\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\EncryptionMethodManager;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException;
use Drupal\key\Entity\Key;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\encrypt\EncryptService;

/**
 * Unit tests for EncryptService class.
 *
 * @ingroup encrypt
 *
 * @group encrypt
 *
 * @coversDefaultClass \Drupal\encrypt\EncryptService
 */
class EncryptServiceTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * Default configuration.
   *
   * @var array[]
   */
  protected $defaultConfig = [
    'encrypt.settings' => [
      'check_profile_status' => TRUE,
      'allow_deprecated_plugins' => FALSE,
    ],
  ];

  /**
   * A mocked EncryptionProfile entity.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionProfile;

  /**
   * A mocked EncryptionMethodManager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptManager;

  /**
   * A mocked KeyRepository.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $keyRepository;

  /**
   * A mocked EncryptionMethod plugin.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionMethod;

  /**
   * A mocked Key entity.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $key;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a mock EncryptionProfile entity.
    $this->encryptionProfile = $this->prophesize(EncryptionProfileInterface::class);

    // Set up a mock EncryptionMethodManager.
    $this->encryptManager = $this->prophesize(EncryptionMethodManager::class);

    // Set up a mock KeyRepository.
    $this->keyRepository = $this->prophesize(KeyRepositoryInterface::class);

    // Set up a mock EncryptionMethod plugin.
    $this->encryptionMethod = $this->prophesize(EncryptionMethodInterface::class);

    // Set up a mock Key entity.
    $this->key = $this->prophesize(Key::class);
  }

  /**
   * Tests loadEncryptionMethods method.
   *
   * @covers ::__construct
   * @covers ::loadEncryptionMethods
   */
  public function testLoadEncryptionMethods() {
    $definitions = [
      'test_encryption_method' => [
        'id' => 'test_encryption_method',
        'title' => "Test encryption method",
      ],
    ];

    $this->encryptManager->getDefinitions()->willReturn($definitions);

    $service = new EncryptService(
      $this->encryptManager->reveal(),
      $this->keyRepository->reveal(),
      $this->getConfigFactoryStub($this->defaultConfig)
    );

    $methods = $service->loadEncryptionMethods();
    $this->assertEquals(['test_encryption_method'], array_keys($methods));
  }

  /**
   * Tests the encrypt & decrypt method.
   *
   * @covers ::__construct
   * @covers ::encrypt
   * @covers ::decrypt
   * @covers ::validate
   *
   * @dataProvider encryptionDataProvider
   */
  public function testEncryptDecrypt($key, $valid_key) {
    // Set up expectations for Key.
    $this->key->getKeyValue()->willReturn($key);

    // This method can decrypt.
    $this->encryptionMethod->canDecrypt()->willReturn(TRUE);

    if ($valid_key) {
      // Set up expectations for encryption method.
      $this->encryptionMethod->encrypt('text_to_encrypt', 'validkey')->willReturn('encrypted_text');
      $this->encryptionMethod->decrypt('text_to_decrypt', 'validkey')->willReturn('decrypted_text');

      // Set up expectations for encryption profile.
      $this->encryptionProfile->getEncryptionKey()->willReturn($this->key);
      $this->encryptionProfile->getEncryptionMethod()->willReturn($this->encryptionMethod);
      $this->encryptionProfile->validate('text_to_encrypt')->willReturn([]);
      $this->encryptionProfile->validate('text_to_decrypt')->willReturn([]);
    }
    else {
      // Set up expectations for encryption profile.
      $this->encryptionProfile->getEncryptionKey()->shouldNotBeCalled();
      $this->encryptionProfile->getEncryptionMethod()->shouldNotBeCalled();
      $this->encryptionProfile->validate('text_to_encrypt')->willReturn(['Validation']);
      $this->encryptionProfile->validate('text_to_decrypt')->willReturn(['Validation']);
      $this->expectException('\Drupal\encrypt\Exception\EncryptException');
    }

    $service = new EncryptService(
      $this->encryptManager->reveal(),
      $this->keyRepository->reveal(),
      $this->getConfigFactoryStub($this->defaultConfig)
    );

    $encrypted_text = $service->encrypt("text_to_encrypt", $this->encryptionProfile->reveal());
    $decrypted_text = $service->decrypt("text_to_decrypt", $this->encryptionProfile->reveal());
    if ($valid_key) {
      $this->assertEquals("encrypted_text", $encrypted_text);
      $this->assertEquals("decrypted_text", $decrypted_text);
    }
  }

  /**
   * Test exception is thrown trying to decrypt an encryption-only method.
   *
   * @covers ::decrypt
   */
  public function testEncryptionOnlyMethods() {
    $this->key->getKeyValue()->willReturn('my-key');
    $this->encryptionMethod->encrypt('text_to_encrypt', 'my-key')->willReturn('encrypted_text');
    $this->encryptionMethod->canDecrypt()->willReturn(FALSE);
    $this->encryptionMethod->decrypt()->shouldNotBeCalled();

    // Set up expectations for encryption profile.
    $this->encryptionProfile->getEncryptionKey()->willReturn($this->key);
    $this->encryptionProfile->getEncryptionMethod()->willReturn($this->encryptionMethod);
    $this->encryptionProfile->validate('text_to_encrypt')->willReturn([]);

    $service = new EncryptService(
      $this->encryptManager->reveal(),
      $this->keyRepository->reveal(),
      $this->getConfigFactoryStub($this->defaultConfig)
    );

    $encrypted_text = $service->encrypt("text_to_encrypt", $this->encryptionProfile->reveal());
    $this->assertEquals("encrypted_text", $encrypted_text);

    // Assert exception is thrown when a method can NOT decrypt.
    $this->expectException(EncryptionMethodCanNotDecryptException::class);
    $service->decrypt($encrypted_text, $this->encryptionProfile->reveal());
  }

  /**
   * Data provider for encrypt / decrypt method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function encryptionDataProvider() {
    return [
      'normal' => ["validkey", TRUE],
      'exception' => ["invalidkey", FALSE],
    ];
  }

}
