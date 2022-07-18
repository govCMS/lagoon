<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;
use PNX\NestedSet\Node;

/**
 * Defines a class for testing the reorder children form.
 *
 * @group entity_hierarchy
 */
class ReorderChildrenContentModerationFunctionalTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use BlockCreationTrait;
  use ContentModerationTestTrait;

  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'entity_test_rev';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
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
    $this->placeBlock('local_tasks_block');
    $this->placeBlock('page_title_block');
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, static::ENTITY_TYPE, static::FIELD_NAME);

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle(static::ENTITY_TYPE, static::ENTITY_TYPE);
    $workflow->save();
    // Force ContentModerationRouteSubscriber to fire, setting the latest revision as the default for the edit route.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    if (!isset($values['moderation_state'])) {
      $values['moderation_state'] = 'published';
    };
    $entity = EntityTestRev::create($values);
    return $entity;
  }

  /**
   * Tests that the reorder form is linked to the current revision when content_moderation is active.
   */
  public function testReorderingForDraftParent() {
    $this->drupalLogin($this->rootUser);
    $this->treeStorage = $this->container->get('entity_hierarchy.nested_set_storage_factory')
      ->get(static::FIELD_NAME, static::ENTITY_TYPE);
    $this->nodeFactory = $this->container->get('entity_hierarchy.nested_set_node_factory');

    $this->parent = $this->doCreateTestEntity([
      'type' => static::ENTITY_TYPE,
      'name' => 'Parent',
      'moderation_state' => 'published',
    ]);
    $this->parent->save();

    $entities = $this->createChildEntities($this->parent->id());
    $this->parentStub = $this->nodeFactory->fromEntity($this->parent);
    $root_node = $this->treeStorage->getNode($this->parentStub);
    $children = $this->treeStorage->findChildren($root_node->getNodeKey());
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

    $this->drupalGet($this->parent->toUrl('edit-form'));
    $this->drupalPostForm(NULL, [
      'name[0][value]' => 'Parent - draft',
      'revision' => TRUE,
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    // Ensure the latest content revision is a draft and we can reorder the children, which are linked to the current published version.
    $this->drupalGet($this->parent->toUrl('edit-form'));
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $assert = $this->assertSession();
    foreach ($entities as $entity) {
      $assert->linkExists($entity->label());
    }

    // Now insert one, and confirm we see this change while the parent is still in draft.
    $name = 'Child 6';
    $entities[$name] = $this->createTestEntity($this->parent->id(), $name, -2);
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $this->assertSession()->linkExists($name);

    // Publish the draft and confirm we see same children.
    $this->drupalGet($this->parent->toUrl('edit-form'));
    $this->assertEquals('Current state Draft', $this->cssSelect('#edit-moderation-state-0-current')[0]->getText());
    $this->drupalPostForm(NULL, [
      'name[0][value]' => 'Parent - published',
      'revision' => TRUE,
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->drupalGet($this->parent->toUrl('edit-form'));
    $this->assertEquals('Current state Published', $this->cssSelect('#edit-moderation-state-0-current')[0]->getText());
    $this->drupalGet($this->parent->toUrl('entity_hierarchy_reorder'));
    $this->assertSession()->linkExists($name);
  }

}
