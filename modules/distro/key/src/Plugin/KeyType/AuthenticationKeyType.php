<?php

namespace Drupal\key\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;

/**
 * Defines a generic key type for authentication.
 *
 * @KeyType(
 *   id = "authentication",
 *   label = @Translation("Authentication"),
 *   description = @Translation("A generic key type to use for a password or API key that does not belong to any other defined key type."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "text_field"
 *   }
 * )
 */
class AuthenticationKeyType extends KeyTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    // Generate a random 16-character password.
    return user_password(16);
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    // Validation of the key value is optional.
  }

}
