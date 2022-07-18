<?php

namespace Drupal\Tests\entity_hierarchy_workbench_access\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\entity_hierarchy\Kernel\EntityHierarchyKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests interaction between entity_hierarchy and workbench_access.
 *
 * @group entity_hierarchy_workbench_access
 */
class EntityHierarchyWorkbenchAccessTest extends EntityHierarchyKernelTestBase {

  const BOOLEAN_FIELD = 'use_as_editorial_section';
  use ContentTypeCreationTrait;
  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'node';

  /**
   * Node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $parentNodeType;

  /**
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $childNodeType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'system',
    'user',
    'options',
    'text',
    'dbal',
    'field',
    'workbench_access',
    'node',
    // Required by WBA.
    'taxonomy',
    'entity_hierarchy_workbench_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    EntityKernelTestBase::setUp();
    $this->installEntitySchema(static::ENTITY_TYPE);
    $this->installEntitySchema('section_association');
    $this->installConfig(['node', 'workbench_access']);
    $this->installSchema('node', ['node_access']);
    $this->parentNodeType = $this->createContentType(['type' => 'section']);
    $this->childNodeType =  $this->createContentType(['type' => 'children']);
    // Only the child has the field.
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, $this->childNodeType->id(), static::FIELD_NAME);
    $this->scheme = AccessScheme::create([
      'id' => 'eh',
      'label' => 'EH',
      'plural_label' => 'EHs',
      'scheme' => 'entity_hierarchy:node__parents',
      'scheme_settings' => [
        'boolean_fields' => [static::BOOLEAN_FIELD],
        'bundles' => ['section', 'children'],
      ],
    ]);
    $this->scheme->save();

    $this->treeStorage = $this->container->get('entity_hierarchy.nested_set_storage_factory')
      ->get(static::FIELD_NAME, static::ENTITY_TYPE);

    $this->nodeFactory = $this->container->get('entity_hierarchy.nested_set_node_factory');

    // Setup a boolean field on both node types.
    $this->setupBooleanEditorialField(static::ENTITY_TYPE, $this->childNodeType->id(), self::BOOLEAN_FIELD);
    $this->setupBooleanEditorialField(static::ENTITY_TYPE, $this->parentNodeType->id(), self::BOOLEAN_FIELD, FALSE);
  }

  /**
   * Creates a new boolean field for flagging entity as section.
   *
   * @param string $entity_type_id
   *   Entity type to add the field to.
   * @param string $bundle
   *   Bundle of field.
   * @param string $field_name
   *   Field name.
   * @param bool $create_field_first
   *   TRUE to create the field storage config too.
   */
  protected function setupBooleanEditorialField($entity_type_id, $bundle, $field_name, $create_field_first = TRUE) {
    if ($create_field_first) {
      $storage = FieldStorageConfig::create([
        'entity_type' => $entity_type_id,
        'field_name' => $field_name,
        'id' => "$entity_type_id.$field_name",
        'type' => 'boolean',
      ]);
      $storage->save();
    }
    $config = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'id' => "$entity_type_id.$bundle.$field_name",
      'label' => Unicode::ucfirst($field_name),
    ]);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = Node::create([
      'title' => isset($values['title']) ? $values['title'] : $this->randomMachineName(),
      'type' => $this->childNodeType->id(),
      'status' => 1,
      'uid' => 1,
    ] + $values);
    return $entity;
  }

  /**
   * Tests integration.
   */
  public function testWorkbenchAccessIntegration() {
    // Get UID 1 out of the way.
    $root = $this->createUser();
    $this->container->get('account_switcher')->switchTo($root);
    // Create a section.
    $section1 = Node::create([
      'type' => $this->parentNodeType->id(),
      'title' => 'Section',
      self::BOOLEAN_FIELD => TRUE,
      'status' => TRUE,
    ]);
    $section1->save();
    // With some children.
    $children_of_section1 = $this->createChildEntities($section1->id());
    // Make the last child also a section.
    $last_child = end($children_of_section1);
    $last_child->{self::BOOLEAN_FIELD} = TRUE;
    $last_child->save();
    $grandchildren = $this->createChildEntities($last_child->id(), 1);

    // Check the tree labels.
    $tree = $this->scheme->getAccessScheme()->getTree();
    $this->assertSame([
      1 => 'Section',
      6 => 'Child 5 (Section)',
    ], array_map(function ($item) {
      return $item['label'];
    }, $tree[self::BOOLEAN_FIELD . '_value']));

    // Create a different section.
    $section2 = Node::create([
      'type' => $this->parentNodeType->id(),
      'title' => 'Section',
      self::BOOLEAN_FIELD => TRUE,
      'status' => TRUE,
    ]);
    $section2->save();
    // With some children.
    $children_of_section2 = $this->createChildEntities($section2->id());
    // Create an editor.
    $editor1 = $this->createUser([], [
      sprintf('create %s content', $this->childNodeType->id()),
      sprintf('delete any %s content', $this->childNodeType->id()),
      sprintf('edit any %s content', $this->childNodeType->id()),
      sprintf('create %s content', $this->parentNodeType->id()),
      sprintf('delete any %s content', $this->parentNodeType->id()),
      sprintf('edit any %s content', $this->parentNodeType->id()),
      'access content',
    ]);
    // Assign them to first section.
    $userSectionStorage = $this->container->get('workbench_access.user_section_storage');
    $userSectionStorage->addUser($this->scheme, $editor1, [$section1->id()]);
    // They should be able to edit/delete from first section and children.
    // But not from different section and children.
    $allowed = array_merge([$section1], $children_of_section1, $grandchildren);
    $disallowed = array_merge([$section2], $children_of_section2);
    $this->checkAccess($allowed, $disallowed, $editor1);
    // Now create a user with rights to a sub-section.
    $editor2 = $this->createUser([], [
      sprintf('create %s content', $this->childNodeType->id()),
      sprintf('delete any %s content', $this->childNodeType->id()),
      sprintf('edit any %s content', $this->childNodeType->id()),
      sprintf('create %s content', $this->parentNodeType->id()),
      sprintf('delete any %s content', $this->parentNodeType->id()),
      sprintf('edit any %s content', $this->parentNodeType->id()),
      'access content',
    ]);
    // Assign them to child section.
    $userSectionStorage->addUser($this->scheme, $editor2, [$last_child->id()]);
    $allowed = [$last_child, reset($grandchildren)];
    array_pop($children_of_section1);
    $disallowed = array_merge($disallowed, $children_of_section1, [$section1]);
    $this->checkAccess($allowed, $disallowed, $editor2);
    // Try to create a node with no section.
    $node = Node::create([
      'type' => $this->childNodeType->id(),
      'title' => 'A new child',
    ]);
    $this->assertEmpty($node->validate());
    $config = $this->container->get('config.factory')->getEditable('workbench_access.settings');
    $config->set('deny_on_empty', TRUE);
    $config->save();
    $this->assertNotEmpty($node->validate());
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $allowed
   *   Entities that should have access to.
   * @param \Drupal\Core\Entity\EntityInterface[] $disallowed
   *   Entities that should not have access to.
   * @param \Drupal\Core\Session\AccountInterface $editor
   *   Account to check access with.
   */
  protected function checkAccess(array $allowed, array $disallowed, AccountInterface $editor) {
    $this->container->get('account_switcher')->switchTo($editor);
    foreach ($allowed as $entity) {
      $this->assertTrue($entity->access('update', $editor));
      $this->assertTrue($entity->access('delete', $editor));
      // Check can nominate as parent.
      $new_child = Node::create([
        'type' => $this->childNodeType->id(),
        'title' => 'A new child',
        self::FIELD_NAME => $entity->id(),
      ]);
      $this->assertEmpty($new_child->validate());
    }
    foreach ($disallowed as $entity) {
      $this->assertFalse($entity->access('update', $editor));
      $this->assertFalse($entity->access('delete', $editor));
      // Check cannot nominate as parent.
      $new_child = Node::create([
        'type' => $this->childNodeType->id(),
        'title' => 'A new child',
        self::FIELD_NAME => $entity->id(),
      ]);
      $this->assertNotEmpty($new_child->validate());
      $new_child->save();
      // But can if the item is previously saved and they're not changing the
      // parent.
      $this->assertEmpty($new_child->validate());
    }
  }

}
