<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PNX\NestedSet\Node;

/**
 * Takes an array of tree nodes & returns matching entities, keyed by tree node.
 */
class EntityTreeNodeMapper implements EntityTreeNodeMapperInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTreeNodeMapper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntitiesForTreeNodesWithoutAccessChecks($entity_type_id, array $nodes, RefinableCacheableDependencyInterface $cache = NULL) {
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple(array_map(function (Node $node) {
      return $node->getId();
    }, $nodes));
    $loadedEntities = new \SplObjectStorage();
    foreach ($nodes as $node) {
      $nodeId = $node->getId();
      $entity = isset($entities[$nodeId]) ? $entities[$nodeId] : FALSE;
      if (!$entity || ($entity->getEntityType()->hasKey('revision') && $node->getRevisionId() != $entity->getRevisionId())) {
        // Bypass non default revisions and deleted items.
        continue;
      }
      $loadedEntities[$node] = $entity;
      if ($cache) {
        $cache->addCacheableDependency($entity);
      }
    }
    return $loadedEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAndAccessCheckEntitysForTreeNodes($entity_type_id, array $nodes, RefinableCacheableDependencyInterface $cache = NULL) {
    $entities = $this->loadEntitiesForTreeNodesWithoutAccessChecks($entity_type_id, $nodes, $cache);
    foreach ($nodes as $node) {
      if (!$entities->offsetExists($node)) {
        continue;
      }
      if (($entity = $entities->offsetGet($node)) && !$entity->access('view label')) {
        // Check access.
        $entities->offsetUnset($node);
      }
    }
    return $entities;
  }

}
