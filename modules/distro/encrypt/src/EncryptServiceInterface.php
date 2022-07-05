<?php

namespace Drupal\encrypt;

/**
 * Class EncryptService.
 *
 * @package Drupal\encrypt
 */
interface EncryptServiceInterface {

  /**
   * Returns the registered encryption method plugins.
   *
   * @param bool $with_deprecated
   *   If TRUE, also return plugins marked as deprecated.
   *
   * @return array
   *   List of encryption methods.
   */
  public function loadEncryptionMethods($with_deprecated = TRUE);

  /**
   * Main encrypt function.
   *
   * @param string $text
   *   The plain text to encrypt.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile entity.
   *
   * @return string
   *   The encrypted string.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   Can throw an EncryptException.
   */
  public function encrypt($text, EncryptionProfileInterface $encryption_profile);

  /**
   * Main decrypt function.
   *
   * @param string $text
   *   The encrypted text to decrypt.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile entity.
   *
   * @return string
   *   The decrypted plain string.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   Can throw an EncryptException.
   * @throws \Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException
   *   Thrown when method can not decrypt (i.e. use a public key).
   */
  public function decrypt($text, EncryptionProfileInterface $encryption_profile);

}
