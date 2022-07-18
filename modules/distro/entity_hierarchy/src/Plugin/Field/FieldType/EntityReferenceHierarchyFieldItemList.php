<?php

namespace Drupal\entity_hierarchy\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\entity_hierarchy\Storage\TreeLockTrait;

/**
 * Defines a field item list for entity reference with hierarchy.
 */
class EntityReferenceHierarchyFieldItemList extends EntityReferenceFieldItemList {

  use TreeLockTrait;

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // The values are now empty.
    if (empty($this->list)) {
      // If this node was in the tree, it needs to be moved to a root node.
      $stubNode = $this->getNestedSetNodeFactory()->fromEntity($this->getEntity());
      $storage = $this->getTreeStorage();
      if (($existingNode = $storage->getNode($stubNode)) && $existingNode->getDepth() > 0) {
        $fieldDefinition = $this->getFieldDefinition();
        $fieldName = $fieldDefinition->getName();
        $entityTypeId = $fieldDefinition->getTargetEntityTypeId();
        $this->lockTree($fieldName, $entityTypeId);
        $storage->moveSubTreeToRoot($existingNode);
        $this->releaseLock($fieldName, $entityTypeId);
      }
    }
    return parent::postSave($update);
  }

  /**
   * Returns the node factory.
   *
   * @return \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   *   The factory.
   */
  protected function getNestedSetNodeFactory() {
    return \Drupal::service('entity_hierarchy.nested_set_node_factory');
  }

  /**
   * Returns the tree storage.
   *
   * @return \PNX\NestedSet\NestedSetInterface
   *   Tree storage.
   */
  protected function getTreeStorage() {
    $fieldDefinition = $this->getFieldDefinition();
    return \Drupal::service('entity_hierarchy.nested_set_storage_factory')->get($fieldDefinition->getName(), $fieldDefinition->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ValidHierarchyReference', []);
    return $constraints;
  }

}
