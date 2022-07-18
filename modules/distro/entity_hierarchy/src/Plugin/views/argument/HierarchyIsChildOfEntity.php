<?php

namespace Drupal\entity_hierarchy\Plugin\views\argument;

/**
 * Argument to limit to children of an entity.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_hierarchy_argument_is_child_of_entity")
 */
class HierarchyIsChildOfEntity extends EntityHierarchyArgumentPluginBase {

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    // Load the actual entity.
    $filtered = FALSE;
    if ($entity = $this->loadEntity()) {
      $stub = $this->nodeKeyFactory->fromEntity($entity);
      if ($node = $this->getTreeStorage()->getNode($stub)) {
        // Query between a range.
        $filtered = TRUE;
        $lower_token = ':lower_' . $this->tableAlias;
        $upper_token = ':upper_' . $this->tableAlias;
        $expression = "$this->tableAlias.$this->realField BETWEEN {$lower_token} and $upper_token AND $this->tableAlias.$this->realField <> {$lower_token}";
        $arguments = [
          $lower_token => $node->getLeft(),
          $upper_token => $node->getRight(),
        ];
        if ($depth = $this->options['depth']) {
          $depth_token = ':depth_' . $this->tableAlias;
          $expression .= " AND $this->tableAlias.depth <= {$depth_token}";
          $arguments[$depth_token] = $node->getDepth() + $depth;
        }
        $this->query->addWhereExpression(0, $expression, $arguments);
      }
    }
    // The parent entity doesn't exist, or isn't in the tree and hence has no
    // children.
    if (!$filtered) {
      // Add a killswitch.
      $this->query->addWhereExpression(0, '1 <> 1');
    }
  }

}
