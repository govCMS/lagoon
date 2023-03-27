<?php

namespace Drupal\config_perms;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Custom perms entity entities.
 */
interface CustomPermsEntityInterface extends ConfigEntityInterface {

  /**
   * Get the permission status.
   */
  public function getStatus();

  /**
   * Get the permission route.
   */
  public function getRoute();

}
