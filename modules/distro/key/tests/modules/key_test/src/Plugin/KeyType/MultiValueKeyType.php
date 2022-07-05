<?php

namespace Drupal\key_test\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Defines a key type that is multi-value.
 *
 * @KeyType(
 *   id = "key_test_multi",
 *   label = @Translation("Multi-value test"),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "key_test_multi",
 *     "accepted" = FALSE
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "first" = {
 *         "label" = @Translation("First"),
 *         "required" = true
 *       },
 *       "second" = {
 *         "label" = @Translation("Second"),
 *         "required" = true
 *       },
 *     }
 *   }
 * )
 */
class MultiValueKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface {

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(
    array $form,
    FormStateInterface $form_state,
    $key_value
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    return json_encode([
      'first' => '',
      'second' => '',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(array $array) {
    return json_encode($array);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($value) {
    return json_decode($value, TRUE);
  }

}
