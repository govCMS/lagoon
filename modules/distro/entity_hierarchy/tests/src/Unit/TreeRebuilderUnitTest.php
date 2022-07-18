<?php

namespace Drupal\Tests\entity_hierarchy\Unit;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_hierarchy\Storage\TreeRebuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Defines a class for testing tree rebuilder.
 *
 * @group entity_hierarchy
 */
class TreeRebuilderUnitTest extends UnitTestCase {

  /**
   * Tests tree sort.
   *
   * Structure
   * - 789
   * -- 991
   * -- 891
   * --- 784
   * ---- 999
   * --- 23
   * - 123
   * - 781
   * - 782
   * -- 783
   */
  public function testTreeSort() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_field_manager = $this->prophesize(EntityFieldManager::class);
    $treeRebuilder = new TreeRebuilder($entity_type_manager->reveal(), $entity_field_manager->reveal());
    $reflection = new \ReflectionClass($treeRebuilder);
    $method = $reflection->getMethod('treeSort');
    $method->setAccessible(TRUE);
    $records = [
      [
        'nid' => 789,
        'field_parent_target_id' => 456,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 123,
        'field_parent_target_id' => 456,
        'field_parent_weight' => -49,
      ],
      [
        'nid' => 781,
        'field_parent_target_id' => 656,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 782,
        'field_parent_target_id' => 656,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 783,
        'field_parent_target_id' => 782,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 999,
        'field_parent_target_id' => 784,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 991,
        'field_parent_target_id' => 789,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 891,
        'field_parent_target_id' => 789,
        'field_parent_weight' => -49,
      ],
      [
        'nid' => 784,
        'field_parent_target_id' => 891,
        'field_parent_weight' => -50,
      ],
      [
        'nid' => 23,
        'field_parent_target_id' => 891,
        'field_parent_weight' => -49,
      ],
    ];
    $result = $method->invoke($treeRebuilder, 'field_parent', $records, 'nid', 'node');
    $this->assertSame([
      789,
      991,
      891,
      784,
      999,
      23,
      123,
      781,
      782,
      783,
    ], array_keys($result));
  }

}
