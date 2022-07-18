<?php

namespace Drupal\entity_hierarchy\Plugin\views\argument;

/**
 * Argument to limit to parent of a revision.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_hierarchy_argument_is_parent_of_entity_revision")
 */
class HierarchyIsParentOfEntityRevision extends HierarchyIsParentOfEntity {

  /**
   * {@inheritdoc}
   */
  protected function loadEntity() {
    $storage = $this->entityTypeManager->getStorage($this->getEntityType());
    return $storage->loadRevision($this->argument) ?: $storage->load($this->argument);
  }

}
