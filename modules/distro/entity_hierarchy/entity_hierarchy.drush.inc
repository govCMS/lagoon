<?php

/**
 * @file
 * Contains drush commands for the module.
 */

/**
 * Implements hook_drush_command().
 */
function entity_hierarchy_drush_command() {
  $items = [];

  $items['entity-hierarchy-rebuild-tree'] = [
    'description' => 'Rebuild tree.',
    'arguments' => [
      'field_name' => dt('Field machine name'),
      'entity_type_id' => dt('Entity type id'),
    ],
    'options' => [],
    'examples' => [
      'drush entity-hierarchy-rebuild-tree field_parents node' => 'Rebuild tree for node field named field_parents.',
    ],
  ];
  return $items;
}

/**
 * Rebuild the tree.
 */
function drush_entity_hierarchy_rebuild_tree($field_name, $entity_type_id) {
  $tasks = \Drupal::service('entity_hierarchy.tree_rebuilder')->getRebuildTasks($field_name, $entity_type_id);
  batch_set($tasks);
  $batch =& batch_get();
  $batch['progressive'] = FALSE;
  drush_backend_batch_process();
}
