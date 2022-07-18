<?php

namespace Drupal\Tests\entity_hierarchy\Traits;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines a trait for common testing methods for entity hierarchy.
 */
trait EntityHierarchyTestTrait {

  /**
   * Test parent.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $parent;

  /**
   * Node key for parent.
   *
   * @var \PNX\NestedSet\NodeKey
   */
  protected $parentStub;

  /**
   * Tree storage.
   *
   * @var \PNX\NestedSet\Storage\DbalNestedSet
   */
  protected $treeStorage;

  /**
   * Node key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $nodeFactory;

  /**
   * Perform additional setup.
   */
  protected function additionalSetup() {
    $this->treeStorage = $this->container->get('entity_hierarchy.nested_set_storage_factory')
      ->get(static::FIELD_NAME, static::ENTITY_TYPE);

    $this->parent = $this->createTestEntity(NULL, 'Parent');
    $this->nodeFactory = $this->container->get('entity_hierarchy.nested_set_node_factory');
    $this->parentStub = $this->nodeFactory->fromEntity($this->parent);
  }

  /**
   * Creates a new entity hierarchy field for the given bundle.
   *
   * @param string $entity_type_id
   *   Entity type to add the field to.
   * @param string $bundle
   *   Bundle of field.
   * @param string $field_name
   *   Field name.
   */
  protected function setupEntityHierarchyField($entity_type_id, $bundle, $field_name) {
    if (!FieldStorageConfig::load("$entity_type_id.$field_name")) {
      $storage = FieldStorageConfig::create([
        'entity_type' => $entity_type_id,
        'field_name' => $field_name,
        'id' => "$entity_type_id.$field_name",
        'type' => 'entity_reference_hierarchy',
        'settings' => [
          'target_type' => $entity_type_id,
        ],
      ]);
      $storage->save();
    }

    if (!FieldConfig::load("$entity_type_id.$bundle.$field_name")) {
      $config = FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
        'id' => "$entity_type_id.$bundle.$field_name",
        'label' => Unicode::ucfirst($field_name),
      ]);
      $config->save();
    }
  }

  /**
   * Create child entities.
   *
   * @param int $parentId
   *   Parent ID.
   * @param int $count
   *   (optional) Number to create. Defaults to 5.
   * @param string $prefix
   *   (Optional) Title prefix.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Child entities
   */
  protected function createChildEntities($parentId, $count = 5, string $prefix = '') {
    $entities = [];
    foreach (range(1, $count) as $i) {
      $label = sprintf('Child %s%d', $prefix, $i);
      $entities[$label] = $this->doCreateChildTestEntity($parentId, $label, -1 * $i);
    }
    return $entities;
  }

  /**
   * Creates a new test entity.
   *
   * @param int|null $parentId
   *   Parent ID.
   * @param string $label
   *   Entity label.
   * @param int $weight
   *   Entity weight amongst sibling, if parent is set.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   New entity.
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
    $entity->save();
    return $entity;
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
    $entity = EntityTest::create($values);
    return $entity;
  }

  /**
   * Creates a new test entity.
   *
   * @param int|null $parentId
   *   Parent ID.
   * @param string $label
   *   Entity label.
   * @param int $weight
   *   Entity weight amongst sibling, if parent is set.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   New entity.
   */
  protected function doCreateChildTestEntity($parentId, $label, $weight) {
    return $this->createTestEntity($parentId, $label, $weight);
  }

  /**
   * Sets up entity form display.
   *
   * @param string $entity_type
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   * @param string $field_name
   *   Field name.
   */
  protected function setupEntityFormDisplay($entity_type, $bundle, $field_name) {
    $this->getEntityFormDisplay($entity_type, $bundle, 'default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_hierarchy_autocomplete',
        'weight' => 20,
      ])
      ->save();
  }

  /**
   * Gets entity form display.
   *
   * @param string $entity_type
   *   Entity type ID.
   * @param string $bundle
   *   Bundle.
   * @param string $form_mode
   *   Form mode.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Form display.
   */
  protected function getEntityFormDisplay($entity_type, $bundle, $form_mode) {
    $entity_form_display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.' . $form_mode);

    // If not found, create a fresh entity object. We do not preemptively create
    // new entity form display configuration entries for each existing entity
    // type and bundle whenever a new form mode becomes available. Instead,
    // configuration entries are only created when an entity form display is
    // explicitly configured and saved.
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $form_mode,
        'status' => TRUE,
      ]);
    }

    return $entity_form_display;
  }

}
