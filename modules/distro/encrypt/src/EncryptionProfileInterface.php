<?php

namespace Drupal\encrypt;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\key\Entity\Key;

/**
 * Provides an interface for defining EncryptionProfile entities.
 */
interface EncryptionProfileInterface extends ConfigEntityInterface {

  /**
   * Gets the encryption method configuration plugin.
   *
   * @return \Drupal\encrypt\EncryptionMethodInterface
   *   The used EncryptionMethod plugin.
   */
  public function getEncryptionMethod();

  /**
   * Gets the plugin ID of the encryption method.
   *
   * @return string
   *   The plugin ID of the selected EncryptionMethod plugin.
   */
  public function getEncryptionMethodId();

  /**
   * Sets the encryption method to use.
   *
   * @param \Drupal\encrypt\EncryptionMethodInterface $encryption_method
   *   The encryption method to use on this encryption profile.
   */
  public function setEncryptionMethod(EncryptionMethodInterface $encryption_method);

  /**
   * Gets the Key entity used in the encryption profile.
   *
   * @return \Drupal\key\Entity\Key
   *   The used Key entity.
   */
  public function getEncryptionKey();

  /**
   * Gets the ID of the Key entity.
   *
   * @return string
   *   The ID of the selected Key entity.
   */
  public function getEncryptionKeyId();

  /**
   * Sets the encryption key to use.
   *
   * @param \Drupal\key\Entity\Key $key
   *   The encryption key to use on this encryption profile.
   */
  public function setEncryptionKey(Key $key);

  /**
   * Validate the EncryptionProfile entity.
   *
   * @param string $text
   *   The text to be encrypted / decrypted.
   *
   * @return array
   *   An array of validation errors. Empty if no errors.
   */
  public function validate($text = NULL);

}
