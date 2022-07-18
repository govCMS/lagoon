<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Tests reordering with revisions.
 *
 * @group entity_hierarchy.
 */
class ReorderChildrenWithRevisionsFunctionalTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use BlockCreationTrait;

  const ENTITY_TYPE = 'entity_test_rev';
  const FIELD_NAME = 'parents';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'entity_test',
    'system',
    'user',
    'dbal',
    'block',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity($parentId, $label = 'Child 1', $weight = 0) {
    $values = [
      'type' => static::ENTITY_TYPE,
      $this->container->get('entity_type.manager')->getDefinition(static::ENTITY_TYPE)->getKey('label') => $label,
    ];
    if ($parentId) {
      $values[static::FIELD_NAME] = [
        'target_id' => $parentId,
        'weight' => $weight,
      ];
    }
    $entity = $this->doCreateTestEntity($values);
    // Create a revision with the wrong weight.
    $entity->setNewRevision(TRUE);
    if ($parentId) {
      $entity->{static::FIELD_NAME}->weight = -1 * $weight;
    }
    $entity->save();
    // And a default revision with the correct weight.
    $entity->setNewRevision(TRUE);
    if ($parentId) {
      $entity->{static::FIELD_NAME}->weight = $weight;
    }
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = EntityTestRev::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, static::ENTITY_TYPE, static::FIELD_NAME);
    $this->additionalSetup();
  }

  /**
   * Tests children reorder form.
   */
  public function testReordering() {
    $entities = $this->createChildEntities($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $mapper = $this->container->get('entity_hierarchy.entity_tree_node_mapper');
    $ancestors = $mapper->loadEntitiesForTreeNodesWithoutAccessChecks('entity_test_rev', $children);
    $labels = $this->getLabels($ancestors);
    $this->assertEquals([
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ], $labels);
    // Now insert one in the middle.
    $name = 'Child 6';
    $entities[$name] = $this->createTestEntity($this->parent->id(), $name, -2);
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $ancestors = $mapper->loadEntitiesForTreeNodesWithoutAccessChecks('entity_test_rev', $children);
    $labels = $this->getLabels($ancestors);
    $this->assertEquals([
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 6',
      'Child 1',
    ], $labels);
    // Now we visit the form for reordering.
    $this->drupalLogin($this->drupalCreateUser([
      'reorder entity_hierarchy children',
      'view test entity',
      'administer entity_test content',
    ]));
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    foreach ($entities as $entity) {
      $assert->linkExists($entity->label());
    }
    // Now move Child 6 to the top.
    $this->submitForm([
      'children[' . $entities[$name]->id() . '][weight]' => -10,
    ], 'Update child order');
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $ancestors = $mapper->loadEntitiesForTreeNodesWithoutAccessChecks('entity_test_rev', $children);
    $labels = $this->getLabels($ancestors);
    $this->assertEquals([
      'Child 6',
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ], $labels);
  }

  /**
   * Get labels.
   *
   * @param \SplObjectStorage $ancestors
   *   Ancestors.
   *
   * @return array
   *   Labels.
   */
  protected function getLabels(\SplObjectStorage $ancestors) {
    $labels = [];
    foreach ($ancestors as $node) {
      $entity = $ancestors->offsetGet($node);
      $labels[] = $entity->label();
    }
    return $labels;
  }

}
