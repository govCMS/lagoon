<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Drupal\node\NodeInterface;
use PNX\NestedSet\Node;

/**
 * Defines a class for looking up a microsite given a node.
 */
class ChildOfMicrositeLookup implements ChildOfMicrositeLookupInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Nested set storage.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * Nested set node key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $nodeKeyFactory;

  /**
   * Constructs a new ChildOfMicrositeLookup.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Storage factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nodeKeyFactory
   *   Key factory.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, NestedSetStorageFactory $nestedSetStorageFactory, NestedSetNodeKeyFactory $nodeKeyFactory) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->nodeKeyFactory = $nodeKeyFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function findMicrositesForNodeAndField(NodeInterface $node, $field_name) {
    $ids = [];
    if ($node->hasField($field_name) &&
    !$node->get($field_name)->isEmpty()) {
      $key = $this->nodeKeyFactory->fromEntity($node);
      /** @var \PNX\NestedSet\NestedSetInterface $nestedSetStorage */
      $nestedSetStorage = $this->nestedSetStorageFactory->get($field_name, 'node');
      $ids = array_map(function (Node $treeNode) {
        return $treeNode->getId();
      }, $nestedSetStorage->findAncestors($key));
    }
    $ids[] = $node->id();
    $entityStorage = $this->entityTypeManager->getStorage('entity_hierarchy_microsite');
    return $entityStorage->loadMultiple($entityStorage
      ->getQuery()
      ->sort('id')
      ->condition('home', $ids, 'IN')
      ->execute());
  }

}
