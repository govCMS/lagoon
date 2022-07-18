<?php

namespace Drupal\entity_hierarchy\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;

/**
 * Defines a class for entity hierarchy implementations for node module.
 */
class NodeEntityHierarchyHandler implements EntityHierarchyHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getAddChildUrl(EntityTypeInterface $entityType, ContentEntityInterface $parent, $bundle, $fieldName) {
    return Url::fromRoute('node.add', [
      'node_type' => $bundle,
    ], [
      'query' => [
        $fieldName => $parent->id(),
      ],
    ]);
  }

}
