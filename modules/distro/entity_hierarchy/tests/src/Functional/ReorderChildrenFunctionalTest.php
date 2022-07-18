<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;
use PNX\NestedSet\Node;

/**
 * Defines a class for testing the reorder children form.
 *
 * @group entity_hierarchy
 */
class ReorderChildrenFunctionalTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use BlockCreationTrait;

  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'entity_test';

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
  protected function setUp() {
    parent::setUp();
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, static::ENTITY_TYPE, static::FIELD_NAME);
    $this->additionalSetup();
    $this->placeBlock('local_tasks_block');
  }

  /**
   * Tests children reorder form.
   */
  public function testReordering() {
    $entities = $this->createChildEntities($this->parent->id());
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $this->assertCount(5, $children);
    $this->assertEquals(array_map(function ($name) use ($entities) {
      return $entities[$name]->id();
    }, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ]), array_map(function (Node $node) {
      return $node->getId();
    }, $children));
    // Now insert one in the middle.
    $name = 'Child 6';
    $entities[$name] = $this->createTestEntity($this->parent->id(), $name, -2);
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $this->assertCount(6, $children);
    $this->assertEquals(array_map(function ($name) use ($entities) {
      return $entities[$name]->id();
    }, [
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 6',
      'Child 1',
    ]), array_map(function (Node $node) {
      return $node->getId();
    }, $children));
    // Now we visit the form for reordering.
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert = $this->assertSession();
    // Access denied.
    $assert->statusCodeEquals(403);
    // Now login.
    $this->drupalLogin($this->drupalCreateUser([
      'reorder entity_hierarchy children',
      'view test entity',
      'administer entity_test content',
    ]));
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert->statusCodeEquals(200);
    foreach ($entities as $entity) {
      $assert->linkExists($entity->label());
    }
    $assert->pageTextContains('Type');
    $assert->pageTextContains('Entity Test Bundle');
    // Now move Child 6 to the top.
    $this->submitForm([
      'children[' . $entities[$name]->id() . '][weight]' => -10,
    ], 'Update child order');
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
    $this->assertCount(6, $children);
    $this->assertEquals(array_map(function ($name) use ($entities) {
      return $entities[$name]->id();
    }, [
      'Child 6',
      'Child 5',
      'Child 4',
      'Child 3',
      'Child 2',
      'Child 1',
    ]), array_map(function (Node $node) {
      return $node->getId();
    }, $children));
    $this->drupalGet($this->parent->toUrl());
    $assert->linkExists('Children');
    $different_test_entity = EntityTestRev::create([
      'type' => 'entity_test_rev',
      'label' => 'No children here',
    ]);
    $different_test_entity->save();
    $this->drupalGet($different_test_entity->toUrl());
    $assert->linkNotExists('Children');
    $this->drupalGet($different_test_entity->toUrl('entity_hierarchy_reorder'));
    // No field, should be not found here.
    $assert->statusCodeEquals(403);
    // Add a new bundle.
    entity_test_create_bundle('someotherbundle');
    $another_different_test_entity = EntityTest::create([
      'type' => 'someotherbundle',
      'label' => 'No children here either',
    ]);
    $another_different_test_entity->save();
    $this->drupalGet($another_different_test_entity->toUrl());
    // Link should show, because entity is valid target bundle.
    $assert->linkExists('Children');
    $this->drupalGet($another_different_test_entity->toUrl('entity_hierarchy_reorder'));
    $assert->statusCodeEquals(200);
    // Now edit the field and disable referencing someotherbundle.
    $field = FieldConfig::load('entity_test.entity_test.parents');
    $settings = $field->getSetting('handler_settings');
    $settings['target_bundles'] = ['entity_test'];
    $field->setSetting('handler_settings', $settings);
    $field->save();
    $another_different_test_entity = EntityTest::create([
      'type' => 'someotherbundle',
      'label' => 'No children here either',
    ]);
    $another_different_test_entity->save();
    $this->drupalGet($another_different_test_entity->toUrl());
    $assert->linkNotExists('Children');
    $this->drupalGet($another_different_test_entity->toUrl('entity_hierarchy_reorder'));
    // No field, should be not found here.
    $assert->statusCodeEquals(403);
  }

  /**
   * Tests add child links.
   */
  public function testAddChildLinks() {
    $this->setupEntityFormDisplay(self::ENTITY_TYPE, self::ENTITY_TYPE, self::FIELD_NAME);

    // Login.
    $this->drupalLogin($this->drupalCreateUser([
      'reorder entity_hierarchy children',
      'view test entity',
      'administer entity_test content',
    ]));
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    // We have no children, and only 1 bundle configured.
    $assert->buttonNotExists('Update child order');
    $assert->elementNotExists('css', '.dropbutton');
    $assert->linkExists('Create new Entity Test Bundle');
    $assert->linkByHrefExists(Url::fromRoute('entity.entity_test.add_form', [
      'type' => 'entity_test',
    ], [
      'query' => [self::FIELD_NAME => $this->parent->id()],
    ])->toString());

    // Create a child and extra bundles. Make sure the buttons update.
    $this->createTestEntity($this->parent->id());
    $bundles = [
      'bundle1' => 'Bundle 1',
      'bundle2' => 'Bundle 2',
      'bundle3' => 'Bundle 3',
      'entity_test' => 'Entity Test Bundle',
    ];
    foreach ($bundles as $id => $name) {
      entity_test_create_bundle($id, $name);
      $this->setupEntityHierarchyField('entity_test', $id, self::FIELD_NAME);
    }
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert->buttonExists('Update child order');

    foreach ($bundles as $id => $name) {
      $assert->linkExists(sprintf('Create new %s', $name));
      $assert->linkByHrefExists(Url::fromRoute('entity.entity_test.add_form', [
        'type' => $id,
      ], [
        'query' => [self::FIELD_NAME => $this->parent->id()],
      ])->toString());
    }
    $this->clickLink('Create new Entity Test Bundle');
    $assert->fieldValueEquals(sprintf('%s[0][target_id][target_id]', self::FIELD_NAME), sprintf('%s (%s)', $this->parent->label(), $this->parent->id()));
  }

}
