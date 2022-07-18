<?php

namespace Drupal\entity_hierarchy\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;

/**
 * Argument to limit to parent of an entity.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_hierarchy_argument_is_sibling_of_entity")
 */
class HierarchyIsSiblingOfEntity extends EntityHierarchyArgumentPluginBase {

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
      if ($node = $this->getTreeStorage()->findParent($stub)) {
        // Query between a range with fixed depth, excluding the original node.
        $filtered = TRUE;
        $lower_token = ':lower_' . $this->tableAlias;
        $upper_token = ':upper_' . $this->tableAlias;
        $depth_token = ':depth_' . $this->tableAlias;
        $expression = "$this->tableAlias.$this->realField BETWEEN {$lower_token} and {$upper_token} AND $this->tableAlias.$this->realField <> {$lower_token} AND $this->tableAlias.depth = {$depth_token}";
        $arguments = [
          $lower_token => $node->getLeft(),
          $upper_token => $node->getRight(),
          $depth_token => $node->getDepth() + 1,
        ];
        if (!$this->options['show_self']) {
          $self_token = ':self'  . $this->tableAlias;
          $expression .= " AND $this->tableAlias.id != {$self_token}";
          $arguments[$self_token] = $stub->getId();
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
    $form['show_self'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show self'),
      '#default_value' => $this->options['show_self'],
      '#description' => $this->t('Filter out the current child from the list of siblings.'),
    ];
    parent::buildOptionsForm($form, $form_state);
    unset($form['depth']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    unset($options['depth']);
    $options['show_self'] = ['default' => FALSE];

    return $options;
  }

}
