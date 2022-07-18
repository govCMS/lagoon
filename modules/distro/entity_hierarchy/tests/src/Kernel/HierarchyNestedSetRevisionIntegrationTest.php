<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use PNX\NestedSet\Node;

/**
 * Tests integration with entity_hierarchy and a revisionable entity.
 *
 * @group entity_hierarchy
 */
class HierarchyNestedSetRevisionIntegrationTest extends HierarchyNestedSetIntegrationTest {

  /**
   * {@inheritdoc}
   */
  const ENTITY_TYPE = 'entity_test_rev';

  /**
   * {@inheritdoc}
   */
  protected function additionalSetup() {
    // The entity_test_rev entity type uses the entity_test schema.
    $this->installEntitySchema('entity_test');
    parent::additionalSetup();
  }

  /**
   * Creates the test entity.
   *
   * @param array $values
   *   Entity values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created entity.
   */
  protected function doCreateTestEntity(array $values) {
    // We use a different entity type here.
    $entity = EntityTestRev::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity($parentId, $label = 'Child 1', $weight = 0, $withRevision = TRUE) {
    $entity = parent::createTestEntity($parentId, $label, $weight);
    if ($withRevision) {
      // Save it twice so we end up with another revision.
      $entity->setNewRevision(TRUE);
      $entity->save();
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateChildTestEntity($parentId, $label, $weight) {
    // Don't want revisions here so pass FALSE for last argument.
    return $this->createTestEntity($parentId, $label, $weight, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getChildren(Node $parent_node) {
    $children = parent::getChildren($parent_node);
    // Limit to latest revisions only.
    $entity_storage = $this->container->get('entity_type.manager')->getStorage(static::ENTITY_TYPE);
    $entities = $entity_storage->loadMultiple();
    $revisions = array_map(function (EntityInterface $entity) {
      return $entity->getRevisionId();
    }, $entities);
    $children = array_values(array_filter($children, function (Node $node) use ($revisions) {
      return in_array($node->getRevisionId(), $revisions, TRUE);
    }));
    return $children;
  }

  /**
   * {@inheritdoc}
   */
  protected function resaveParent() {
    $this->parent->setNewRevision(TRUE);
    $this->parent->save();
    $this->parentStub = $this->nodeFactory->fromEntity($this->parent);
  }

}
