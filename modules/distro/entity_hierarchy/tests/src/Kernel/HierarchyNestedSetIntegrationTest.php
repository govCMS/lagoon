<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\Core\Entity\EntityInterface;
use PNX\NestedSet\Node;

/**
 * Tests integration with entity_hierarchy.
 *
 * @group entity_hierarchy
 */
class HierarchyNestedSetIntegrationTest extends EntityHierarchyKernelTestBase {

  /**
   * Tests simple storage in nested set tables.
   */
  public function testNestedSetStorageSimple() {
    $child = $this->createTestEntity($this->parent->id());
    $this->assertSimpleParentChild($child);
  }

  /**
   * Tests ordered storage in nested set tables.
   *
   * @group entity_hierarchy_ordering
   */
  public function testNestedSetOrdering() {
    // Test for weight ordering of inserts.
    $entities = $this->createChildEntities($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $this->assertChildOrder($root_node, $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ]);
    // Now insert one in the middle.
    $name = 'Child 6';
    $entities[$name] = $this->createTestEntity($this->parent->id(), $name, -2);
    $this->assertChildOrder($root_node, $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 6',
      'Child 1',
    ]);
  }

  /**
   * Tests removing parent reference.
   */
  public function testRemoveParentReference() {
    $child = $this->createTestEntity($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $this->assertSimpleParentChild($child);
    $child->set(static::FIELD_NAME, NULL);
    $child->save();
    $children = $this->getChildren($root_node);
    $this->assertCount(0, $children);
    $child_node = $this->treeStorage->getNode($this->nodeFactory->fromEntity($child));
    $this->assertEquals(0, $child_node->getDepth());
  }

  /**
   * Tests deleting child node.
   */
  public function testDeleteChild() {
    $child = $this->createTestEntity($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $children = $this->getChildren($root_node);
    $this->assertCount(1, $children);
    $child->delete();
    $children = $this->getChildren($root_node);
    $this->assertCount(0, $children);
  }

  /**
   * Tests deleting parent node reparents children.
   */
  public function testDeleteParent() {
    $child = $this->createTestEntity($this->parent->id());
    $child2 = $this->createTestEntity($this->parent->id());
    $this->createTestEntity($child->id());
    $grandchild2 = $this->createTestEntity($child2->id());
    $grandchildNodeKey = $this->nodeFactory->fromEntity($grandchild2);
    $grandchild2_node = $this->treeStorage->getNode($grandchildNodeKey);
    $this->assertEquals(2, $grandchild2_node->getDepth());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $children = $this->getChildren($root_node);
    $this->assertCount(2, $children);
    // Now we delete child2, grandchild2 should go up a layer.
    $child2->delete();
    $children = $this->getChildren($root_node);
    $this->assertCount(2, $children);
    $reload = function ($id) {
      return \Drupal::entityTypeManager()->getStorage(static::ENTITY_TYPE)->loadUnchanged($id);
    };
    $grandchild2 = $reload($grandchild2->id());
    $field_name = static::FIELD_NAME;
    $this->assertNotNull($grandchild2);
    $this->assertEquals($this->parent->id(), $grandchild2->{$field_name}->target_id);
    $grandchildNodeKey = $this->nodeFactory->fromEntity($grandchild2);
    $grandchild2_node = $this->treeStorage->getNode($grandchildNodeKey);
    $this->assertEquals(1, $grandchild2_node->getDepth());
    // Confirm field values were updated.
    $this->parent->delete();
    // Grandchild2 and child should now be parentless.
    $grandchild2 = $reload($grandchild2->id());
    $grandchild2_node = $this->treeStorage->getNode($this->nodeFactory->fromEntity($grandchild2));
    $this->assertEquals(0, $grandchild2_node->getDepth());
    $grandchild2 = $reload($grandchild2->id());
    $child = $reload($grandchild2->id());
    // Confirm field values were updated.
    $this->assertEquals(NULL, $grandchild2->{self::FIELD_NAME}->target_id);
    $this->assertEquals(NULL, $child->{self::FIELD_NAME}->target_id);
  }

  /**
   * Tests deleting child node with grandchildren.
   */
  public function testDeleteChildWithGrandChildren() {
    $child = $this->createTestEntity($this->parent->id());
    $grand_child = $this->createTestEntity($child->id(), 'Grandchild 1', 1);
    $this->assertSimpleParentChild($child);
    $this->assertSimpleParentChild($grand_child, $child, 1);
    $child->delete();
    $this->assertSimpleParentChild($grand_child, $this->parent);
  }

  /**
   * Tests removing parent reference with grandchildren.
   */
  public function testRemoveParentReferenceWithGrandChildren() {
    $child = $this->createTestEntity($this->parent->id());
    $grand_child = $this->createTestEntity($child->id(), 'Grandchild 1', 1);
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $this->assertSimpleParentChild($child);
    $this->assertSimpleParentChild($grand_child, $child, 1);
    $child->set(static::FIELD_NAME, NULL);
    $child->save();
    $children = $this->getChildren($root_node);
    $this->assertCount(0, $children);
    // Should now be at top level.
    $this->assertSimpleParentChild($grand_child, $child);
  }

  /**
   * Tests saving with existing parent (no value change).
   */
  public function testNestedSetStorageSimpleUpdate() {
    $child = $this->createTestEntity($this->parent->id());
    $this->assertSimpleParentChild($child);
    $child->save();
    $this->assertSimpleParentChild($child);
  }

  /**
   * Tests saving with existing parent and sibling (no value change).
   */
  public function testNestedSetStorageWithSiblingUpdate() {
    $child = $this->createTestEntity($this->parent->id(), 'Child 1', 1);
    $sibling = $this->createTestEntity($this->parent->id(), 'Child 2', 2);
    $this->assertParentWithTwoChildren($child, $sibling);
    $child->save();
    $this->assertParentWithTwoChildren($child, $sibling);
  }

  /**
   * Tests moving parents.
   */
  public function testNestedSetStorageMoveParent() {
    $child = $this->createTestEntity($this->parent->id(), 'Child 1', 1);
    $parent2 = $this->createTestEntity(NULL, 'Parent 2');
    $parent2->save();
    $this->assertSimpleParentChild($child);
    $child->set(static::FIELD_NAME, $parent2->id());
    $child->save();
    $this->assertSimpleParentChild($child, $parent2);
  }

  /**
   * Tests moving tree.
   */
  public function testNestedSetStorageMoveParentWithChildren() {
    $child = $this->createTestEntity($this->parent->id(), 'Child 1', 1);
    $parent2 = $this->createTestEntity(NULL, 'Parent 2');
    $grandchild = $this->createTestEntity($child->id(), 'Grandchild 1', 1);
    $this->assertSimpleParentChild($child);
    $this->assertSimpleParentChild($grandchild, $child, 1);
    $child->set(static::FIELD_NAME, $parent2->id());
    $child->save();
    $this->assertSimpleParentChild($child, $parent2);
    $this->assertSimpleParentChild($grandchild, $child, 1);
  }

  /**
   * Tests moving parents with weight ordering.
   *
   * @group entity_hierarchy_ordering
   */
  public function testNestedSetStorageMoveParentWithSiblingOrdering() {
    $child = $this->createTestEntity($this->parent->id(), 'Cousin 1', -2);
    $parent2 = $this->createTestEntity(NULL, 'Parent 2');
    $child_entities = $this->createChildEntities($parent2->id(), 5);
    $child_entities['Cousin 1'] = $child;
    $this->assertSimpleParentChild($child);
    $child->set(static::FIELD_NAME, $parent2->id());
    $child->save();
    $this->assertChildOrder($this->treeStorage->getNode($this->nodeFactory->fromEntity($parent2)), $child_entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Cousin 1',
      'Child 1',
    ]);
  }

  /**
   * Tests moving from out of tree, into tree.
   */
  public function testNestedSetParentToChild() {
    $child = $this->createTestEntity(NULL);
    $child->set(static::FIELD_NAME, $this->parent->id());
    $child->save();
    $this->assertSimpleParentChild($child);
  }

  /**
   * Tests moving from out of tree, into tree with existing siblings.
   *
   * @group entity_hierarchy_ordering
   */
  public function testNestedSetParentToChildWithSiblings() {
    $child = $this->createTestEntity(NULL, 'Once was a parent');
    $entities = $this->createChildEntities($this->parent->id());
    $entities[$child->label()] = $child;
    $child->{static::FIELD_NAME} = [
      'target_id' => $this->parent->id(),
      'weight' => -2,
    ];
    $child->save();
    $this->assertChildOrder($this->treeStorage->getNode($this->parentStub), $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Once was a parent',
      'Child 1',
    ]);
  }

  /**
   * Test saving the parent after adding children.
   */
  public function testNestedSetResaveParent() {
    // Test for weight ordering of inserts.
    $entities = $this->createChildEntities($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $this->assertChildOrder($root_node, $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ]);
    // Now insert one in the middle.
    $name = 'Child 6';
    $entities[$name] = $this->createTestEntity($this->parent->id(), $name, -2);
    $this->assertChildOrder($root_node, $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 6',
      'Child 1',
    ]);
    $this->resaveParent();
    $this->assertChildOrder($this->treeStorage->getNode($this->parentStub), $entities, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 6',
      'Child 1',
    ]);
  }

  /**
   * Re-saves the parent, with option to include new revision.
   */
  protected function resaveParent() {
    $this->parent->save();
    $this->parentStub = $this->nodeFactory->fromEntity($this->parent);
  }

  /**
   * Test parent/child relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $child
   *   Child node.
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   (optional) Parent to test relationship with, defaults to the one
   *   created in setup if not passed.
   * @param int $baseDepth
   *   (optional) Base depth to add, defaults to 0.
   */
  protected function assertSimpleParentChild(EntityInterface $child, EntityInterface $parent = NULL, $baseDepth = 0) {
    $parent = $parent ?: $this->parent;
    $root_node = $this->treeStorage->getNode($this->nodeFactory->fromEntity($parent));
    $this->assertNotEmpty($root_node);
    $this->assertEquals($parent->id(), $root_node->getId());
    $this->assertEquals($this->getEntityRevisionId($parent), $root_node->getRevisionId());
    $this->assertEquals(0 + $baseDepth, $root_node->getDepth());
    $children = $this->getChildren($root_node);
    $this->assertCount(1, $children);
    $first = reset($children);
    $this->assertEquals($child->id(), $first->getId());
    $this->assertEquals($this->getEntityRevisionId($child), $first->getRevisionId());
    $this->assertEquals(1 + $baseDepth, $first->getDepth());
  }

  /**
   * Test parent/child relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $child
   *   Child node.
   * @param \Drupal\Core\Entity\EntityInterface $sibling
   *   Sibling node.
   */
  protected function assertParentWithTwoChildren(EntityInterface $child, EntityInterface $sibling) {
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $this->assertNotEmpty($root_node);
    $this->assertEquals($this->parent->id(), $root_node->getId());
    $this->assertEquals($this->getEntityRevisionId($this->parent), $root_node->getRevisionId());
    $this->assertEquals(0, $root_node->getDepth());
    $children = $this->getChildren($root_node);
    $this->assertCount(2, $children);
    $first = reset($children);
    $this->assertEquals($child->id(), $first->getId());
    $this->assertEquals($this->getEntityRevisionId($child), $first->getRevisionId());
    $this->assertEquals(1, $first->getDepth());
    $last = end($children);
    $this->assertEquals($sibling->id(), $last->getId());
    $this->assertEquals($this->getEntityRevisionId($sibling), $last->getRevisionId());
    $this->assertEquals(1, $last->getDepth());
  }

  /**
   * Gets the revision ID for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity revision ID if it exists, otherwise entity ID.
   *
   * @return int
   *   Revision ID.
   */
  protected function getEntityRevisionId(EntityInterface $entity) {
    $id = $entity->id();
    if (!$revision_id = $entity->getRevisionId()) {
      $revision_id = $id;
    }
    return $revision_id;
  }

  /**
   * Gets children of a given node.
   *
   * @param \PNX\NestedSet\Node $parent_node
   *   Parent node.
   *
   * @return \PNX\NestedSet\Node[]
   *   Children
   */
  protected function getChildren(Node $parent_node) {
    return $this->treeStorage->findChildren($parent_node->getNodeKey());
  }

  /**
   * Asserts children in given order.
   *
   * @param \PNX\NestedSet\Node $parent_node
   *   Parent node.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities keyed by label.
   * @param string[] $order
   *   Array of titles in order.
   *
   * @return \PNX\NestedSet\Node[]
   *   Children.
   */
  protected function assertChildOrder(Node $parent_node, array $entities, array $order) {
    $children = $this->getChildren($parent_node);
    $this->assertCount(count($order), $children);
    $this->assertEquals(array_map(function ($name) use ($entities) {
      return $entities[$name]->id();
    }, $order), array_map(function (Node $node) {
      return $node->getId();
    }, $children));
    return $children;
  }

}
