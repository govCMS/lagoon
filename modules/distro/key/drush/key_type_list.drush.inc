<?php

/**
 * @file
 * Display a list of available key types.
 */

/**
 * Get a list of available key types.
 */
function drush_key_type_list() {
  $result = [];

  $group = drush_get_option('group');

  $plugins = \Drupal::service('plugin.manager.key.key_type')->getDefinitions();
  foreach ($plugins as $id => $plugin) {
    if (!isset($group) || $plugin['group'] == $group) {
      $row = [];
      $row['id'] = $id;
      $row['description'] = $plugin['description'];

      $result[$id] = $row;
    }
  }

  return $result;
}
