<?php

namespace Drupal\Tests\encrypt\Kernel;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;

/**
 * Tests asymmetrical_encryption_method encryption method.
 *
 * @group encrypt
 */
class AsymmetricalEncryptionMethod extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'encrypt',
    'encrypt_test',
  ];

  /**
   * Test encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $encryptionProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a 128bit testkey.
    $key = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key->save();

    // Create test encryption profiles.
    $this->encryptionProfile = EncryptionProfile::create([
      'id' => 'test_encryption_profile',
      'label' => 'Test Encryption profile',
      'encryption_method' => 'asymmetrical_encryption_method',
      'encryption_key' => $key->id(),
    ]);
    $this->encryptionProfile->save();
  }

  /**
   * Test public profile/method/key.
   */
  public function testEncryptDecrypt() {

    /** @var \Drupal\encrypt\EncryptServiceInterface $profile */
    $service = $this->container->get('encryption');

    $text_encrypted = $service->encrypt('Test to encrypt', $this->encryptionProfile);
    $this->assertEquals('###encrypted###', $text_encrypted);

    // The encryption service throw an exception when trying to decrypt through
    // a method with 'can_decrypt' FALSE.
    $this->expectException(EncryptionMethodCanNotDecryptException::class);
    $service->decrypt($text_encrypted, $this->encryptionProfile);

  }

}
