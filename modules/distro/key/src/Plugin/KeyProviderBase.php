<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\key\KeyInterface;

/**
 * Defines a base class for Key Provider plugins.
 */
abstract class KeyProviderBase extends KeyPluginBase implements KeyProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    switch ($options['key_type_group']) {
      case 'authentication':
        $options['visible_right'] = 4;
        $obscured_value = static::obscureValue($key_value, $options);
        break;

      case 'encryption':
        $options['visible_right'] = 0;
        $options['fixed_length'] = 30;
        $obscured_value = static::obscureValue($key_value, $options);
        break;

      default:
        $obscured_value = $key_value;
    }

    return $obscured_value;
  }

  /**
   * Helper method to obscure a value.
   *
   * @param string $key_value
   *   The key value to obscure.
   * @param array $options
   *   Options to use when obscuring the value.
   *
   * @return string
   *   The obscured key value.
   */
  protected static function obscureValue($key_value, array $options = []) {
    // Add default options.
    $options += [
      'replacement_character' => '*',
      'fixed_length' => '',
      'visible_right' => 4,
    ];

    if ($options['visible_right'] > 0) {
      $visible_right_chars = substr($key_value, $options['visible_right'] * -1);
    }
    else {
      $visible_right_chars = '';
    }

    $obscured_chars = '';
    if ($options['fixed_length']) {
      $obscured_chars = str_repeat($options['replacement_character'], $options['fixed_length'] - $options['visible_right']);
    }
    elseif (strlen($key_value) - $options['visible_right'] > 0) {
      $obscured_chars = str_repeat($options['replacement_character'], strlen($key_value) - $options['visible_right']);
    }

    return $obscured_chars . $visible_right_chars;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(KeyInterface $key, EntityStorageInterface $storage, $update = TRUE) {
    // Post-save actions are optional.
  }

}
