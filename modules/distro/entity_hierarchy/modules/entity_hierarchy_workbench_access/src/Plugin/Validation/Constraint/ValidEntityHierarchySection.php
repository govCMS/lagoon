<?php

namespace Drupal\entity_hierarchy_workbench_access\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating allowed parent.
 *
 * @Constraint(
 *   id = "ValidHierarchySection",
 *   label = @Translation("Valid hierarchy selection", context = "Validation"),
 * )
 */
class ValidEntityHierarchySection extends Constraint  {

  /**
   * Violation message. Use the same message as FormValidator.
   *
   * Note that the name argument is not sanitized so that translators only have
   * one string to translate. The name is sanitized in self::validate().
   *
   * @var string
   */
  public $message = 'You are not allowed to create content in this section.';

}
