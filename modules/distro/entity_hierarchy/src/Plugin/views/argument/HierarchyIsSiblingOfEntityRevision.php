<?php

namespace Drupal\entity_hierarchy\Plugin\views\argument;

/**
 * Argument to limit to parent of a revision.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_hierarchy_argument_is_sibling_of_entity_revision")
 */
class HierarchyIsSiblingOfEntityRevision extends HierarchyIsSiblingOfEntity {

  /**
   * {@inheritdoc}
   */
  protected function loadEntity() {
    $storage = $this->entityTypeManager->getStorage($this->getEntityType());
    return $storage->loadRevision($this->argument) ?: $storage->load($this->argument);
  }

}
