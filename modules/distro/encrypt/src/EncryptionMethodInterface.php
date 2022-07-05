<?php

namespace Drupal\encrypt;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for EncryptionMethod plugins.
 *
 * @package Drupal\encrypt
 */
interface EncryptionMethodInterface extends PluginInspectionInterface {

  /**
   * Encrypt text.
   *
   * @param string $text
   *   The text to be encrypted.
   * @param string $key
   *   The key to encrypt the text with.
   *
   * @return string
   *   The encrypted text
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   Thrown when encryption fails.
   */
  public function encrypt($text, $key);

  /**
   * Decrypt text.
   *
   * @param string $text
   *   The text to be decrypted.
   * @param string $key
   *   The key to decrypt the text with.
   *
   * @return string
   *   The decrypted text
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   Thrown when decryption fails.
   * @throws \Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException
   *   The method should throw this exception when the plugin can not decrypt
   *   (i.e. use a public key).
   */
  public function decrypt($text, $key);

  /**
   * Check dependencies for the encryption method.
   *
   * @param string $text
   *   The text to be checked.
   * @param string $key
   *   The key to be checked.
   *
   * @return array
   *   An array of error messages, providing info on missing dependencies.
   */
  public function checkDependencies($text = NULL, $key = NULL);

  /**
   * Get the label.
   *
   * @return string
   *   The label for this EncryptionMethod plugin.
   */
  public function getLabel();

  /**
   * Define if encryption method can also decrypt.
   *
   * @return bool
   *   TRUE if encryption method decrypt, FALSE otherwise.
   */
  public function canDecrypt();

  /**
   * Define if encryption method is deprecated.
   *
   * @return bool
   *   TRUE if encryption method is deprecated, FALSE otherwise.
   */
  public function isDeprecated();

}
