<?php

namespace Drupal\key;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Key entity.
 */
interface KeyInterface extends ConfigEntityInterface {

  /**
   * Gets the description of the key.
   *
   * @return string
   *   The description of this key.
   */
  public function getDescription();

  /**
   * Returns the configured plugins for the key.
   *
   * @return \Drupal\key\Plugin\KeyPluginInterface[]
   *   An array of plugins, indexed by plugin type.
   */
  public function getPlugins();

  /**
   * Returns the configured plugin of the requested type.
   *
   * @param string $type
   *   The plugin type to return.
   *
   * @return \Drupal\key\Plugin\KeyPluginInterface
   *   The plugin.
   */
  public function getPlugin($type);

  /**
   * Sets a plugin of the requested type and plugin ID.
   *
   * @param string $type
   *   The plugin type.
   * @param string $id
   *   The plugin ID.
   */
  public function setPlugin($type, $id);

  /**
   * Returns the configured key type for the key.
   *
   * @return \Drupal\key\Plugin\KeyTypeInterface
   *   The key type associated with the key.
   */
  public function getKeyType();

  /**
   * Returns the configured key provider for the key.
   *
   * @return \Drupal\key\Plugin\KeyProviderInterface
   *   The key provider associated with the key.
   */
  public function getKeyProvider();

  /**
   * Returns the configured key input for the key.
   *
   * @return \Drupal\key\Plugin\KeyInputInterface
   *   The key input associated with the key.
   */
  public function getKeyInput();

  /**
   * Gets the value of the key.
   *
   * @return string
   *   The value of the key.
   */
  public function getKeyValue();

  /**
   * Gets the values of the key.
   *
   * @return array
   *   The values of the key.
   */
  public function getKeyValues();

  /**
   * Sets the value of the key.
   *
   * @param string $key_value
   *   The key value to set.
   *
   * @return string|bool
   *   The key value or FALSE if the value could not be set, because the
   *   provider does not support setting a key value, for instance.
   */
  public function setKeyValue($key_value);

}
