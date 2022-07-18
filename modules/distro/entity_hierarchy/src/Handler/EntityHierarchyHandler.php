<?php

namespace Drupal\entity_hierarchy\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;

/**
 * Defines a class for provide entity-type specific entity hierarchy logic.
 */
class EntityHierarchyHandler implements EntityHierarchyHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getAddChildUrl(EntityTypeInterface $entityType, ContentEntityInterface $parent, $bundle, $fieldName) {
    $routeName = "entity.{$entityType->id()}.add_form";
    return Url::fromRoute($routeName, [
      $entityType->getKey('bundle') => $bundle,
    ], [
      'query' => [
        $fieldName => $parent->id(),
      ],
    ]);
  }

}
