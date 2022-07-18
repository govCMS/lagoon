<?php

namespace Drupal\entity_hierarchy_microsite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for a content entity to store menu item overrides.
 */
interface MicrositeMenuItemOverrideInterface extends ContentEntityInterface {

  /**
   * Gets target UUID.
   *
   * @return string
   *   Target.
   */
  public function getTarget();

  /**
   * Gets parent plugin ID.
   *
   * @return string
   *   Parent plugin ID.
   */
  public function getParent();

  /**
   * Is enabled.
   *
   * @return bool
   *   TRUE if is enabled.
   */
  public function isEnabled();

  /**
   * Is expanded.
   *
   * @return bool
   *   TRUE if is expanded.
   */
  public function isExpanded();

  /**
   * Gets weight.
   *
   * @return int
   *   Weight.
   */
  public function getWeight();

}
