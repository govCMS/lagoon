<?php

namespace Drupal\encrypt_test\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;

/**
 * TestEncryptionMethod testing class.
 *
 * @EncryptionMethod(
 *   id = "test_encryption_method",
 *   title = @Translation("Test Encryption method"),
 *   description = "A test encryption method.",
 *   key_type = {"encryption"}
 * )
 */
class TestEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface {

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
    return str_rot13($key . $text);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    $decoded = str_rot13($text);
    // Strip out key, to retrieve original text.
    if (substr($decoded, 0, strlen($key)) == $key) {
      return substr($decoded, strlen($key));
    }
    else {
      return "invalid key";
    }
  }

}
