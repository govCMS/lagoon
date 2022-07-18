<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

/**
 * Defines a class for testing base field integration.
 *
 * @group entity_hierarchy
 */
class BaseFieldTest extends HierarchyNestedSetIntegrationTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy_test_base_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setupEntityHierarchyField($entity_type_id, $bundle, $field_name) {
    // Nil op. We use a base field here.
  }

}
