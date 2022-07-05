<?php

/**
 * @file
 * Display a list of available key providers.
 */

/**
 * Get a list of available key providers.
 */
function drush_key_provider_list() {
  $result = [];

  $storage_method = drush_get_option('storage-method');

  $plugins = \Drupal::service('plugin.manager.key.key_provider')->getDefinitions();
  foreach ($plugins as $id => $plugin) {
    if (!isset($storage_method) || $plugin['storage_method'] == $storage_method) {
      $row = [];
      $row['id'] = $id;
      $row['description'] = $plugin['description'];

      $result[$id] = $row;
    }
  }

  return $result;
}
