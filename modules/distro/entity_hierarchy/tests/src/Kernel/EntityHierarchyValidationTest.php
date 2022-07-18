<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class for testing validation constraint.
 *
 * @group entity_hierarchy
 */
class EntityHierarchyValidationTest extends EntityHierarchyKernelTestBase {

  /**
   * Tests validation.
   */
  public function testValidation() {
    // Create root user.
    $this->createUser();
    $child = $this->createTestEntity($this->parent->id());

    // Try and reference child.
    $this->parent->{self::FIELD_NAME}->target_id = $child->id();
    $this->doTestViolations($child);

    // Try and reference self.
    $this->parent->{self::FIELD_NAME}->target_id = $this->parent->id();
    $this->doTestViolations($this->parent);

    // Try and reference grandchild.
    $grandchild = $this->createTestEntity($child->id());
    $this->parent->{self::FIELD_NAME}->target_id = $grandchild->id();
    $this->doTestViolations($grandchild);
  }

  /**
   * Tests violations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $referencedEntity
   *   Referenced entity.
   */
  protected function doTestViolations(EntityInterface $referencedEntity) {
    $violations = $this->parent->validate();
    $this->assertCount(1, $violations);
    $violation = $violations[0];
    $this->assertEquals(sprintf('This entity (entity_test: %s) cannot be referenced as it is either a child or the same entity.', $referencedEntity->id()), strip_tags($violation->getMessage()));
    $this->assertEquals(self::FIELD_NAME . '.0.target_id', $violation->getPropertyPath());
  }

}
