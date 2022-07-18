<?php

namespace Drupal\entity_hierarchy\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a class for provide entity-type specific entity hierarchy logic.
 */
interface EntityHierarchyHandlerInterface {

  /**
   * Gets an add child URL.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity type.
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent
   *   Parent entity.
   * @param string $bundle
   *   Child bundle.
   * @param string $fieldName
   *   Child field name.
   *
   * @return \Drupal\Core\Url
   *   Url to add new child.
   */
  public function getAddChildUrl(EntityTypeInterface $entityType, ContentEntityInterface $parent, $bundle, $fieldName);

}
