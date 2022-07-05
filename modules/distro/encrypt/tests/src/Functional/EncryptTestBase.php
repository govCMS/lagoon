<?php

namespace Drupal\Tests\encrypt\Functional;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Entity\Key;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a base class for tests.
 */
abstract class EncryptTestBase extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = ['key', 'encrypt', 'encrypt_test'];

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A list of testkeys.
   *
   * @var \Drupal\key\Entity\Key[]
   */
  protected $testKeys;

  /**
   * A list of test encryption profiles.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile[]
   */
  protected $encryptionProfiles;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->createTestKeys();
    $this->createTestEncryptionProfiles();
  }

  /**
   * Creates test keys for usage in tests.
   */
  protected function createTestKeys() {
    // Create a 128bit testkey.
    $key_128 = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key_128->save();
    $this->testKeys['testing_key_128'] = $key_128;

    // Create a 256bit testkey.
    $key_256 = Key::create([
      'id' => 'testing_key_256',
      'label' => 'Testing Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '256'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbitmustbesixteenbit'],
    ]);
    $key_256->save();
    $this->testKeys['testing_key_256'] = $key_256;
  }

  /**
   * Creates test encryption profiles for usage in tests.
   */
  protected function createTestEncryptionProfiles() {
    // Create test encryption profiles.
    $encryption_profile_1 = EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ]);
    $encryption_profile_1->save();
    $this->encryptionProfiles['encryption_profile_1'] = $encryption_profile_1;

    $encryption_profile_2 = EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration' => ['mode' => 'CFB'],
      'encryption_key' => $this->testKeys['testing_key_256']->id(),
    ]);
    $encryption_profile_2->save();
    $this->encryptionProfiles['encryption_profile_2'] = $encryption_profile_2;
  }

}
