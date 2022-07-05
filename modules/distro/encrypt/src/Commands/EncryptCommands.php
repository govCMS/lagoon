<?php

namespace Drupal\encrypt\Commands;

use Drush\Commands\DrushCommands;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptService;

/**
 * Class EncryptCommands.
 *
 * @package Drupal\encrypt\Commands
 */
class EncryptCommands extends DrushCommands {

  /**
   * Encrypt service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encrypt;

  /**
   * EncryptCommands constructor.
   *
   * @param \Drupal\encrypt\EncryptService $encrypt
   *   The encrypt service object.
   */
  public function __construct(EncryptService $encrypt) {
    $this->encrypt = $encrypt;
  }

  /**
   * Encrypt text with the provided encryption profile.
   *
   * @param string $encryption_profile_name
   *   The machine name of the encryption profile to use.
   * @param string $text
   *   The text to encrypt.
   * @param array $options
   *   The command options array.
   *
   * @command encrypt:encrypt
   * @option base64 Output the encrypted text in base64 encoded format.
   * @usage drush encrypt:encrypt profile_name 'text to encrypt'
   *   Encrypts the given text with the specified encryption profile.
   * @usage drush encrypt:encrypt --base64 profile_name 'text to encrypt'
   *   Encrypts the given text with the specified encryption profile and
   *   base64-encodes output.
   * @aliases encrypt,enc
   *
   * @throws \Exception
   */
  public function encrypt($encryption_profile_name, $text, array $options = ['base64' => FALSE]) {
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      throw new \Exception(dt("Encryption profile @profile could not be loaded.", ['@profile' => $encryption_profile]));
    }

    $encrypted_text = $this->encrypt->encrypt($text, $encryption_profile);
    if ($options['base64']) {
      $encrypted_text = base64_encode($encrypted_text);
    }

    $this->output()->writeln($encrypted_text);
  }

  /**
   * Decrypt text with the provided encryption profile.
   *
   * @param string $encryption_profile_name
   *   The machine name of the encryption profile to use.
   * @param string $text
   *   The text to encrypt.
   * @param array $options
   *   The command options array.
   *
   * @command encrypt:decrypt
   * @usage drush encrypt:decrypt profile_name 'text to decrypt'
   *   Decrypts the given text with the specified encryption profile.
   * @usage drush encrypt:decrypt --base64 profile_name 'text to decrypt'
   *   Decrypts the given base64-encoded text with the specified
   *   encryption profile.
   * @aliases decrypt,dec
   *
   * @throws \Exception
   */
  public function decrypt($encryption_profile_name, $text, array $options = ['base64' => FALSE]) {
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      throw new \Exception('error', dt('Encryption profile "@name" could not be loaded.', ['@name' => $encryption_profile_name]));
    }

    if ($options['base64']) {
      $text = base64_decode($text);
    }

    $decrypted_text = $this->encrypt->decrypt($text, $encryption_profile);

    $this->output()->writeln($decrypted_text);
  }

  /**
   * Validates the encryption profile to check if all dependencies are met.
   *
   * @param string $encryption_profile_name
   *   The machine name of the encryption profile to use.
   *
   * @command encrypt:validate-profile
   * @usage drush encrypt:validate-profile profile_name
   *   Validates the given encryption profile.
   * @aliases evp
   *
   * @return array
   *   A list of errors.
   *
   * @throws \Exception
   */
  public function validateProfile($encryption_profile_name) {
    $output = [];
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      throw new \Exception('', dt('Encryption profile "@name" could not be loaded.', ['@name' => $encryption_profile_name]));
    }

    $errors = $encryption_profile->validate();
    if ($errors) {
      foreach ($errors as $error_msg) {
        $output[] = ['error' => $error_msg];
      }

      return $output;
    }

    $this->logger()->notice('Encryption profile validates successfully.');
  }

}
