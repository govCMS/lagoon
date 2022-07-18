<?php

namespace Drupal\entity_hierarchy\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Constraint(
 *   id = "ValidHierarchyReference",
 *   label = @Translation("Entity Reference valid hierarchy reference", context = "Validation")
 * )
 */
class ValidHierarchyReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'This entity (%type: %id) cannot be referenced as it is either a child or the same entity.';

}
