<?php

namespace Drupal\Tests\encrypt\Functional;

/**
 * Tests the encrypt admin UI and encryption / decryption service.
 *
 * @group encrypt
 */
class EncryptTest extends EncryptTestBase {

  /**
   * Test adding an encryption profile and encrypting / decrypting with it.
   */
  public function testEncryptAndDecrypt() {
    // Create an encryption profile config entity.
    $this->drupalGet('admin/config/system/encryption/profiles/add');

    // Check if the plugin exists.
    // Encryption method option is present.
    $this->assertOption('edit-encryption-method', 'test_encryption_method');
    // Encryption method text is present.
    $this->assertText('Test Encryption method');

    $edit = [
      'encryption_method' => 'test_encryption_method',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $edit = [
      'id' => 'test_encryption_profile',
      'label' => 'Test encryption profile',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $encryption_profile = \Drupal::service('entity_type.manager')->getStorage('encryption_profile')->load('test_encryption_profile');
    $this->assertNotEmpty($encryption_profile, 'Encryption profile was successfully saved.');

    // Test the encryption service with our encryption profile.
    $test_string = 'testing 123 &*#';
    $enc_string = \Drupal::service('encryption')->encrypt($test_string, $encryption_profile);
    $this->assertEquals('zhfgorfvkgrraovggrfgvat 123 &*#', $enc_string, 'The encryption service is not properly processing');

    // Test the decryption service with our encryption profile.
    $dec_string = \Drupal::service('encryption')->decrypt($enc_string, $encryption_profile);
    $this->assertEquals($test_string, $dec_string, 'The decryption service is not properly processing');
  }

  /**
   * Tests validation of encryption profiles.
   */
  public function testProfileValidation() {
    // Create an encryption profile config entity.
    $this->drupalGet('admin/config/system/encryption/profiles/add');

    // Check if the plugin exists.
    // Encryption method option is present.
    $this->assertOption('edit-encryption-method', 'test_encryption_method');
    // Encryption method text is present.
    $this->assertText('Test Encryption method');

    $edit = [
      'encryption_method' => 'test_encryption_method',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Create an encryption profile.
    $edit = [
      'id' => 'test_encryption_profile',
      'label' => 'Test encryption profile',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Test the encryption profile edit form.
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_encryption_profile');
    // The warning about editing an encryption profile is visible.
    $this->assertText('Be extremely careful when editing an encryption profile! It may result in making data encrypted with this profile unreadable. Are you sure you want to edit this profile?');
    // The encryption method field is not visible.
    $this->assertNoFieldByName('encryption_method', NULL);
    // The encryption key field is not visible.
    $this->assertNoFieldByName('encryption_key', NULL);

    $this->drupalPostForm(NULL, [], 'Edit');

    // The warning about editing an encryption profile is no longer visible.
    $this->assertNoText('Be extremely careful when editing an encryption profile! It may result in making data encrypted with this profile unreadable. Are you sure you want to edit this profile?');
    // The encryption method field is now visible.
    $this->assertFieldByName('encryption_method', NULL);
    // The encryption key field is now visible.
    $this->assertFieldByName('encryption_key', NULL);

    // Check that the 128 bit key exists so display changes don't give false
    // positives on the key deletion assertions below.
    $this->drupalGet('admin/config/system/encryption/profiles');
    $this->assertText('Key 128 bit');

    // Now delete the testkey.
    $this->drupalGet('admin/config/system/keys');
    $this->clickLink('Delete');
    // Warning is shown that linked dependency will also be deleted when
    // deleting the key.
    $this->assertText('Encryption Profile');
    // The encryption profile linked dependency is listed as the linked
    // dependency.
    $this->assertText('Test encryption profile');
    $this->drupalPostForm(NULL, [], 'Delete');

    // Check that the 128 bit key no longer exists.
    $this->drupalGet('admin/config/system/encryption/profiles');
    $this->assertNoText('Key 128 bit');

    // Test "check_profile_status" setting.
    $this->config('encrypt.settings')
      ->set('check_profile_status', FALSE)
      ->save();
    $this->drupalGet('admin/config/system/encryption/profiles');
    $this->assertNoText('The key linked to this encryption profile does not exist.');
  }

  /**
   * Test Encryption profile entity with encryption method plugin config forms.
   */
  public function testEncryptionMethodConfig() {
    // Create an encryption profile config entity.
    $this->drupalGet('admin/config/system/encryption/profiles/add');

    // Check if the plugin exists.
    // Config encryption method option is present.
    $this->assertOption('edit-encryption-method', 'config_test_encryption_method');
    // Config encryption method text is present.
    $this->assertText('Config Test Encryption method');

    // Check encryption method without config.
    $edit = [
      'label' => 'Test',
      'id' => 'test_encryption_profile',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
      'encryption_method' => 'test_encryption_method',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Saved the Test encryption profile.');
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_encryption_profile');
    // First, confirm we want to edit the encryption profile.
    $this->drupalPostForm(NULL, [], 'Edit');
    // Test encryption method has no config form.
    $this->assertNoFieldByName('encryption_method_configuration[mode]', NULL);

    // Check encryption method with config.
    $this->drupalGet('admin/config/system/encryption/profiles/add');
    $edit = [
      'label' => 'Test 2',
      'id' => 'test_encryption_profile_2',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
      'encryption_method' => 'config_test_encryption_method',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Saved the Test 2 encryption profile.');
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_encryption_profile_2');
    // First, confirm we want to edit the encryption profile.
    $this->drupalPostForm(NULL, [], 'Edit');
    // Config test encryption method has config form.
    $this->assertFieldByName('encryption_method_configuration[mode]', NULL);
    // Config form shows element.
    $this->assertOptionByText('encryption_method_configuration[mode]', 'CBC');

    // Save encryption profile with configured encryption method.
    $this->drupalGet('admin/config/system/encryption/profiles/add');
    $edit = [
      'id' => 'test_config_encryption_profile',
      'label' => 'Test encryption profile',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check if encryption method configuration was succesfully saved.
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_config_encryption_profile');
    // First, confirm we want to edit the encryption profile.
    $this->drupalPostForm(NULL, [], 'Edit');
    $edit = [
      'encryption_method_configuration[mode]' => 'CBC',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    /** @var \Drupal\encrypt\EncryptionProfileInterface $encryption_profile */
    $encryption_profile = \Drupal::service('entity_type.manager')->getStorage('encryption_profile')->load('test_config_encryption_profile');
    $this->assertNotEmpty($encryption_profile, 'Encryption profile was successfully saved');
    $encryption_method = $encryption_profile->getEncryptionMethod();
    $encryption_method_config = $encryption_method->getConfiguration();
    $this->assertEquals(['mode' => 'CBC'], $encryption_method_config, 'Encryption method config correctly saved');

    // Change the encryption method to a non-config one.
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_config_encryption_profile');

    // First, confirm we want to edit the encryption profile.
    $this->drupalPostForm(NULL, [], 'Edit');

    // Select encryption method without config.
    $edit = [
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('admin/config/system/encryption/profiles/manage/test_config_encryption_profile');
    // First, confirm we want to edit the encryption profile.
    $this->drupalPostForm(NULL, [], 'Edit');
    // Test encryption method has no config form.
    $this->assertNoFieldByName('encryption_method_configuration[mode]', NULL);
  }

}
