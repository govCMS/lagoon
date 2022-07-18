<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Defines a class for testing views integration.
 *
 * @group entity_hierarchy
 */
class ViewsIntegrationTest extends EntityHierarchyKernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * Module containing the test views.
   *
   * @var string
   */
  protected $testViewModule = 'entity_hierarchy_test_views';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'entity_test',
    'system',
    'user',
    'dbal',
    'field',
    'views',
    'entity_hierarchy_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function additionalSetup() {
    parent::additionalSetup();
    $this->installConfig($this->testViewModule);
    $this->installConfig('system');
    $this->installSchema('system', ['key_value_expire']);
  }

  /**
   * Gets the views argument from a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return int
   *   The views argument/contextual filter value.
   */
  protected function getArgumentFromEntity(ContentEntityInterface $entity) : int {
    return $entity->id();
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationDirectChildren() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_1', [$this->getArgumentFromEntity($this->parent)]);
    $this->assertCount(3, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationIncludingGrandChildren() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
      [
        'name' => 'Child 5',
        'id' => 9,
      ],
      [
        'name' => 'Child 4',
        'id' => 8,
      ],
      [
        'name' => 'Child 3',
        'id' => 7,
      ],
      [
        'name' => 'Child 2',
        'id' => 6,
      ],
      [
        'name' => 'Child 1',
        'id' => 5,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_2', [$this->getArgumentFromEntity($this->parent)]);
    $this->assertCount(8, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationParents() {
    $children = $this->createChildEntities($this->parent->id(), 1);
    $child = reset($children);
    $grandchildren = $this->createChildEntities($child->id(), 1);
    // Tree is as follows
    // 1     : Parent
    // - 2   : Child 1
    // - - 3 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Parent',
        'id' => 1,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_3', [$this->getArgumentFromEntity(reset($grandchildren))]);
    $this->assertCount(2, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views sibling integration.
   */
  public function testViewsIntegrationSiblings() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_4', [$this->getArgumentFromEntity($child)]);
    $this->assertCount(2, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views sibling integration with show_self enabled.
   */
  public function testViewsIntegrationSiblingsShowSelf() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing siblings with the show_self option enabled.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_5', [$this->getArgumentFromEntity($child)]);
    $this->assertCount(3, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests the depth field.
   */
  public function testDepthField() {
    $children = $this->createChildEntities($this->parent->id(), 1);
    $child = reset($children);
    $this->createChildEntities($child->id(), 1);
    // Tree is as follows
    // 1     : Parent
    // - 2   : Child 1
    // - - 3 : Child 1.
    $executable = Views::getView('entity_hierarchy_test_fields_view');
    $output = $executable->preview('field_depth');
    $output = \Drupal::service('renderer')->renderRoot($output);

    $this->assertStringContainsString('Parent at depth 0', $output);
    $this->assertStringContainsString('Child 1 at depth 2', $output);
  }

  /**
   * Tests the child summary field.
   */
  public function testChildrenSummaryField() {
    $children = $this->createChildEntities($this->parent->id(), 1, 'First');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 2, 'Second');
    foreach ($children as $key => $child) {
      $children = $this->createChildEntities($child->id(), 3, "Third-{$key}");
    }
    $child = reset($children);
    $this->createChildEntities($child->id(), 1, 'Fourth');
    $this->createChildEntities($this->parent->id(), 1, 'Other');

    $executable = Views::getView('entity_hierarchy_test_fields_view');
    $output = $executable->preview('summary_child_counts');
    $output = \Drupal::service('renderer')->renderRoot($output);

    $this->assertStringContainsString('Parent child counts are 2 / 2 / 6 / 1', $output);
    $this->assertStringContainsString('Child First1 child counts are 2 / 6 / 1', $output);
    $this->assertStringContainsString('Child Second2 child counts are 3 / 1', $output);
    $this->assertStringContainsString('Child Third-Child Second21 child counts are 1', $output);
    $this->assertStringContainsString('Child Second1 child counts are 3', $output);
  }

  /**
   * Tests the relationship to the root node..
   */
  public function testRelationshipRoot() {
    $children = $this->createChildEntities($this->parent->id(), 1, 'First');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 2, 'Second');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Third');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Fourth');
    // Tree is as follows.
    // 1     : First 1
    // - 2   : Second 1
    // - 3   : Second 2
    // -- 4  : Third 1
    // --- 5 : Fourth 1.
    $executable = Views::getView('entity_hierarchy_test_relationships_view');
    $output = $executable->preview('root');
    $output = trim(\Drupal::service('renderer')->renderRoot($output));

    $this->assertStringContainsString('Parent is root of Child First1', $output);
    $this->assertStringContainsString('Parent is root of Child Third1', $output);
    $this->assertStringContainsString('Parent is root of Child Fourth1', $output);
    $this->assertStringNotContainsString('1 is root', $output);
    $this->assertEquals(6, substr_count($output, ' is root of'));
  }

  /**
   * Tests the relationship to the parent node.
   */
  public function testRelationshipParent() {
    $children = $this->createChildEntities($this->parent->id(), 1, 'First');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 2, 'Second');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Third');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Fourth');
    // Tree is as follows.
    // 1     : First 1
    // - 2   : Second 1
    // - 3   : Second 2
    // -- 4  : Third 1
    // --- 5 : Fourth 1.
    $executable = Views::getView('entity_hierarchy_test_relationships_view');
    $output = $executable->preview('parent');
    $output = \Drupal::service('renderer')->renderRoot($output);

    $this->assertStringContainsString('Child First1 is parent of Child Second2', $output);
    $this->assertStringContainsString('Child Second1 is parent of Child Third1', $output);
    $this->assertStringContainsString('Child Third1 is parent of Child Fourth1', $output);
    $this->assertStringNotContainsString('Child Fourth1 is parent of', $output);
    $this->assertEquals(5, substr_count($output, ' is parent of Child'));
  }

  /**
   * Tests the relationship to the children nodes.
   */
  public function testRelationshipChildren() {
    $children = $this->createChildEntities($this->parent->id(), 1, 'First');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 2, 'Second');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Third');
    $child = reset($children);
    $children = $this->createChildEntities($child->id(), 1, 'Fourth');
    // Tree is as follows.
    // 1     : First 1
    // - 2   : Second 1
    // - 3   : Second 2
    // -- 4  : Third 1
    // --- 5 : Fourth 1.
    $executable = Views::getView('entity_hierarchy_test_relationships_view');
    $output = $executable->preview('children');
    $output = trim(\Drupal::service('renderer')->renderRoot($output));

    $this->assertStringContainsString('Child Second1 is child of Child First1', $output);
    $this->assertStringContainsString('Child Third1 is child of Child Second1', $output);
    $this->assertStringContainsString('Child Fourth1 is child of Child Third1', $output);
    $this->assertStringNotContainsString('child of Child Fourth1', $output);
    $this->assertEquals(5, substr_count($output, ' is child of'));
  }

}
