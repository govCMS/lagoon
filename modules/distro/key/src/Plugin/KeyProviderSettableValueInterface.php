<?php

namespace Drupal\key\Plugin;

use Drupal\key\KeyInterface;

/**
 * Defines an interface for provider plugins that allow setting a key value.
 */
interface KeyProviderSettableValueInterface {

  /**
   * Sets the value of a key.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key whose value will be set.
   * @param string $key_value
   *   The key value.
   *
   * @return bool
   *   TRUE if successful, FALSE if unsuccessful.
   */
  public function setKeyValue(KeyInterface $key, $key_value);

  /**
   * Deletes the value of a key.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key whose value will be deleted.
   *
   * @return string
   *   TRUE if successful, FALSE if unsuccessful.
   */
  public function deleteKeyValue(KeyInterface $key);

}
