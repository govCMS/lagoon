<?php

namespace Drupal\entity_hierarchy\Information;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for determining if an entity is a parent candidate.
 */
interface ParentCandidateInterface {

  /**
   * Gets all fields that allow referencing this entity as a parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to get parent candidate fields for.
   *
   * @return array
   *   Field names that would allow referencing this entity as a parent.
   */
  public function getCandidateFields(EntityInterface $entity);

  /**
   * Gets all bundles that allow referencing this entity as a parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to get parent candidate bundles for.
   *
   * @return array
   *   Bundles that support this entity as parent, keyed by field name.
   */
  public function getCandidateBundles(EntityInterface $entity);

}
