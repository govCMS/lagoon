<?php

namespace Drupal\key;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * KeyConfigOverrideInterface interface.
 */
interface KeyConfigOverrideInterface extends ConfigEntityInterface {

  /**
   * Gets the configuration type.
   *
   * @return string
   *   The configuration type.
   */
  public function getConfigType();

  /**
   * Gets the configuration name.
   *
   * @return string
   *   The configuration name.
   */
  public function getConfigName();

  /**
   * Gets the configuration prefix.
   *
   * @return string
   *   The configuration prefix.
   */
  public function getConfigPrefix();

  /**
   * Gets the configuration item.
   *
   * @return string
   *   The configuration item.
   */
  public function getConfigItem();

  /**
   * Gets the ID of the key to use for the override.
   *
   * @return string
   *   The ID of the key.
   */
  public function getKeyId();

  /**
   * Return the key entity associated with the override.
   *
   * @return \Drupal\key\KeyInterface
   *   The key entity.
   */
  public function getKey();

}
