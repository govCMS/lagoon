<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use PNX\NestedSet\NodeKey;

/**
 * Defines a class for turning Drupal entities into nested set nodes.
 */
class NestedSetNodeKeyFactory {

  /**
   * Creates a new node from an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to convert into nested set node.
   *
   * @return \PNX\NestedSet\NodeKey
   *   New node.
   */
  public function fromEntity(ContentEntityInterface $entity) {
    $id = $entity->id();
    if (!$revision_id = $entity->getRevisionId()) {
      if (!$revision_id = $entity->getLoadedRevisionId()) {
        $revision_id = $id;
      }
    }
    return new NodeKey($id, $revision_id);
  }

}
