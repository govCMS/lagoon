<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use PNX\NestedSet\Node;
use PNX\NestedSet\NodeKey;

/**
 * Defines a class for testing rebuilding.
 *
 * @group entity_hierarchy
 */
class RebuildTest extends EntityHierarchyKernelTestBase {

  /**
   * Tests rebuilding.
   */
  public function testRebuild() {
    $this->createChildEntities($this->parent->id());
    $expected = [
      new Node(new NodeKey('1', '1'), '1', '12', '0'),
      new Node(new NodeKey('6', '6'), '2', '3', '1'),
      new Node(new NodeKey('5', '5'), '4', '5', '1'),
      new Node(new NodeKey('4', '4'), '6', '7', '1'),
      new Node(new NodeKey('3', '3'), '8', '9', '1'),
      new Node(new NodeKey('2', '2'), '10', '11', '1'),
    ];
    $this->assertEquals($expected, $this->treeStorage->getTree());
    // Now mess with the database.
    $this->container->get('database')->update('nested_set_parents_entity_test')
      ->fields([
        'left_pos' => 4,
        'right_pos' => 5,
      ])
      ->condition('id', 2)
      ->execute();
    $this->assertNotEquals($expected, $this->treeStorage->getTree());
    $rebuild_tasks = $this->container->get('entity_hierarchy.tree_rebuilder')->getRebuildTasks(self::FIELD_NAME, self::ENTITY_TYPE);
    batch_set($rebuild_tasks);
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
    $this->assertEquals($expected, $this->treeStorage->getTree());
  }

}
