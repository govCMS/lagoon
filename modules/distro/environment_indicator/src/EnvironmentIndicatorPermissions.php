<?php

namespace Drupal\environment_indicator;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Undocumented class.
 */
class EnvironmentIndicatorPermissions {

  use StringTranslationTrait;

  /**
   * Returns the dynamic permissions array.
   *
   * @return array
   *   The permissions configuration array.
   */
  public function permissions() {
    $permissions = [];
    // TODO: Learn how to inject the EntityConfig loader.
    $environments = [];
    foreach ($environments as $environment) {
      $permissions['access environment indicator ' . $environment->machine] = [
        'title' => $this->t('See environment indicator for %name', ['%name' => $environment->name]),
        'description' => $this->t('See the environment indicator if the user is in the %name environment.', ['%name' => $environment->name]),
      ];
    }
    return $permissions;
  }

}
