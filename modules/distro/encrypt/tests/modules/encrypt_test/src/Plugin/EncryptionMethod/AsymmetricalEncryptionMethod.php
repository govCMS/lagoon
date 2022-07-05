<?php

namespace Drupal\encrypt_test\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt_test\Exception\AsymmetricalEncryptionMethodCanNotDecryptException;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;

/**
 * Encryption-only encryption method, it can NOT decrypt.
 *
 * @EncryptionMethod(
 *   id = "asymmetrical_encryption_method",
 *   title = @Translation("Asymmetrical Encryption method"),
 *   description = "A method which can only encrypt but not decrypt.",
 *   key_type = {"encryption"},
 *   can_decrypt = FALSE
 * )
 */
class AsymmetricalEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    return '###encrypted###';
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    // This method should throw EncryptionMethodCanNotDecryptException, however
    // if we do it here from the test we won't be able to understand if the
    // exception is thrown by the 'encryption' service or by this method. In a
    // normal scenario method with 'can_decrypt' FALSE can and should throw
    // EncryptionMethodCanNotDecryptException.
    throw new AsymmetricalEncryptionMethodCanNotDecryptException();
  }

}
