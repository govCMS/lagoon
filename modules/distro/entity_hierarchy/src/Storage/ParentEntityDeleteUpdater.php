<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for updating the tree when a parent is deleted.
 */
class ParentEntityDeleteUpdater extends ParentEntityReactionBase {

  /**
   * Tree node mapper.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $treeNodeMapper;

  /**
   * Constructs a new ParentEntityRevisionUpdater object.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Nested set storage factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nodeKeyFactory
   *   Node key factory.
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate service.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $treeNodeMapper
   *   Tree node mapper.
   */
  public function __construct(NestedSetStorageFactory $nestedSetStorageFactory, NestedSetNodeKeyFactory $nodeKeyFactory, ParentCandidateInterface $parentCandidate, EntityTreeNodeMapperInterface $treeNodeMapper) {
    parent::__construct($nestedSetStorageFactory, $nodeKeyFactory, $parentCandidate);
    $this->treeNodeMapper = $treeNodeMapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static(
      $container->get('entity_hierarchy.nested_set_storage_factory'),
      $container->get('entity_hierarchy.nested_set_node_factory'),
      $container->get('entity_hierarchy.information.parent_candidate'),
      $container->get('entity_hierarchy.entity_tree_node_mapper')
    ))->setLockBackend($container->get('lock'));
  }

  /**
   * Moves children to their grandparent or root.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent
   *   Parent being deleted.
   */
  public function moveChildren(ContentEntityInterface $parent) {
    if (!$parent->isDefaultRevision()) {
      // We don't do anything here.
      return;
    }
    if (!$fields = $this->parentCandidate->getCandidateFields($parent)) {
      // There are no fields that could point to this entity.
      return;
    }
    $stubNode = $this->nodeKeyFactory->fromEntity($parent);
    foreach ($fields as $field_name) {
      /** @var \Pnx\NestedSet\NestedSetInterface $storage */
      $storage = $this->nestedSetStorageFactory->get($field_name, $parent->getEntityTypeId());
      if ($children = $storage->findChildren($stubNode)) {
        $parentNode = $storage->findParent($stubNode);
        $childEntities = $this->treeNodeMapper->loadEntitiesForTreeNodesWithoutAccessChecks($parent->getEntityTypeId(), $children);
        foreach ($childEntities as $child_node) {
          if (!$childEntities->offsetExists($child_node)) {
            continue;
          }
          $child_entity = $childEntities->offsetGet($child_node);
          $child_entity->{$field_name}->target_id = ($parentNode ? $parentNode->getId() : NULL);
          if ($child_entity->getEntityType()->hasKey('revision')) {
            // We don't want a new revision here.
            $child_entity->setNewRevision(FALSE);
          }
          $child_entity->save();
        }
      }
      $this->lockTree($field_name, $parent->getEntityTypeId());
      if ($existingNode = $storage->getNode($stubNode)) {
        $storage->deleteNode($existingNode);
      }
      $this->releaseLock($field_name, $parent->getEntityTypeId());
    }
  }

}
