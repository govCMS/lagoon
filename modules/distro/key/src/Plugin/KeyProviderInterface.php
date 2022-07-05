<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\key\KeyInterface;

/**
 * Provides an interface for Key Provider plugins.
 */
interface KeyProviderInterface {

  /**
   * Returns the value of a key.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key whose value will be retrieved.
   *
   * @return string
   *   The key value.
   */
  public function getKeyValue(KeyInterface $key);

  /**
   * Obscures a key value.
   *
   * @param string $key_value
   *   The key value to obscure.
   * @param array $options
   *   Options to use when obscuring the value.
   *
   * @return string
   *   The obscured key value.
   */
  public static function obscureKeyValue($key_value, array $options);

  /**
   * Allows a key provider to perform actions after a key entity is saved.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key entity that was saved.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(KeyInterface $key, EntityStorageInterface $storage, $update = TRUE);

}
