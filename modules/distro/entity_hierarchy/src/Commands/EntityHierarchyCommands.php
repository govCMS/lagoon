<?php

namespace Drupal\entity_hierarchy\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands.
 */
class EntityHierarchyCommands extends DrushCommands {

  /**
   * TreeRebuilder instance.
   *
   * @var \Drupal\entity_hierarchy\Storage\TreeRebuilder
   */
  protected $treeRebuilder;

  /**
   * EntityHierarchyCommands constructor.
   *
   * @param \Drupal\entity_hierarchy\Storage\TreeRebuilder $treeRebuilder
   */
  public function __construct($treeRebuilder) {
    parent::__construct();

    $this->treeRebuilder = $treeRebuilder;
  }

  /**
   * Rebuild tree.
   *
   * @param string $field_name
   *   Field machine name
   * @param string $entity_type_id
   *   Entity type id
   *
   * @usage drush entity-hierarchy:rebuild-tree field_parents node
   *   Rebuild tree for node field named field_parents.
   *
   * @command entity-hierarchy:rebuild-tree
   * @aliases entity-hierarchy-rebuild-tree
   */
  public function hierarchyRebuildTree($field_name, $entity_type_id) {
    $tasks = $this->treeRebuilder->getRebuildTasks($field_name, $entity_type_id);
    batch_set($tasks);
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

}
