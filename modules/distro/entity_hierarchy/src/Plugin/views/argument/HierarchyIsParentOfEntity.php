<?php

namespace Drupal\entity_hierarchy\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;

/**
 * Argument to limit to parent of an entity.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_hierarchy_argument_is_parent_of_entity")
 */
class HierarchyIsParentOfEntity extends EntityHierarchyArgumentPluginBase {

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
        // Child comes between our left and right.
        $filtered = TRUE;
        $child_left_token = ':child_left_' . $this->tableAlias;
        $expression = "$this->tableAlias.$this->realField < {$child_left_token} AND $this->tableAlias.right_pos > {$child_left_token}";
        $arguments = [
          $child_left_token => $node->getLeft(),
        ];
        if ($depth = $this->options['depth']) {
          $depth_token = ':depth_' . $this->tableAlias;
          $expression .= " AND $this->tableAlias.depth <= {$depth_token}}";
          $arguments[$depth_token] = $node->getDepth() - $depth;
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

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['depth']['#description'] = $this->t('Filter to parent that are at most this many levels higher than their parent. E.g. for immediate parent, select 1.');
  }

}
