<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Takes an array of tree nodes & returns matching entities, keyed by tree node.
 */
interface EntityTreeNodeMapperInterface {

  /**
   * Loads Drupal entities for given tree nodes.
   *
   * @param string $entity_type_id
   *   Entity Type ID.
   * @param \PNX\NestedSet\Node[] $nodes
   *   Tree node to load entity for.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cache
   *   (optional) Cache metadata.
   *
   * @return \SplObjectStorage
   *   Map of entities keyed by node.
   */
  public function loadEntitiesForTreeNodesWithoutAccessChecks($entity_type_id, array $nodes, RefinableCacheableDependencyInterface $cache = NULL);

  /**
   * Loads Drupal entities for given tree nodes and checks access.
   *
   * @param string $entity_type_id
   *   Entity Type ID.
   * @param \PNX\NestedSet\Node[] $nodes
   *   Tree node to load entity for.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cache
   *   (optional) Cache metadata.
   *
   * @return \SplObjectStorage
   *   Map of entities keyed by node.
   */
  public function loadAndAccessCheckEntitysForTreeNodes($entity_type_id, array $nodes, RefinableCacheableDependencyInterface $cache = NULL);

}
