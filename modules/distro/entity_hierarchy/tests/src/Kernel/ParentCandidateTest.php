<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;

/**
 * Defines a class for testing ParentCandidate.
 *
 * @group entity_hierarchy
 */
class ParentCandidateTest extends EntityHierarchyKernelTestBase {

  /**
   * Tests field candidates.
   */
  public function testGetParentCandidate() {
    $this->installEntitySchema('entity_test_rev');
    $parentCandidate = $this->container->get('entity_hierarchy.information.parent_candidate');
    $this->assertEquals(['parents'], $parentCandidate->getCandidateFields($this->parent));

    entity_test_create_bundle('fooey');
    $entity = EntityTest::create(['type' => 'fooey']);
    $entity->save();
    $this->assertEquals(['parents'], $parentCandidate->getCandidateFields($entity));

    // Add a bundle limit.
    $field = FieldConfig::load('entity_test.entity_test.parents');
    $settings = $field->getSetting('handler_settings');
    $settings['target_bundles'] = ['entity_test'];
    $field->setSetting('handler_settings', $settings);
    $field->save();

    $this->assertEquals([], $parentCandidate->getCandidateFields($entity));

    $entity_rev = EntityTestRev::create();
    $entity_rev->save();
    $this->assertEquals([], $parentCandidate->getCandidateFields($entity_rev));
  }

  /**
   * Tests bundles.
   */
  public function testGetBundles() {
    $parentCandidate = $this->container->get('entity_hierarchy.information.parent_candidate');
    entity_test_create_bundle('fooey', 'Fooey');
    entity_test_create_bundle('bar');
    $this->setupEntityHierarchyField(self::ENTITY_TYPE, 'fooey', self::FIELD_NAME);
    $this->setupEntityHierarchyField(self::ENTITY_TYPE, 'bar', self::FIELD_NAME);
    $bundles = $parentCandidate->getCandidateBundles($this->parent);
    $this->assertEquals([
      'entity_test',
      'fooey',
      'bar',
    ], array_keys($bundles[self::FIELD_NAME]));
    $this->assertEquals(['label' => 'Fooey'], $bundles[self::FIELD_NAME]['fooey']);

    // Add a bundle limit - prevent fooey bundle from referencing the
    // entity_test bundle.
    $field = FieldConfig::load('entity_test.fooey.parents');
    $settings = $field->getSetting('handler_settings');
    $settings['target_bundles'] = ['bar'];
    $field->setSetting('handler_settings', $settings);
    $field->save();
    $bundles = $parentCandidate->getCandidateBundles($this->parent);
    $this->assertEquals([
      'entity_test',
      'bar',
    ], array_keys($bundles[self::FIELD_NAME]));
  }

}
