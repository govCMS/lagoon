<?php

/**
 * @file
 * Display a list of available keys.
 */

use Drush\Utils\StringUtils;

/**
 * Get a list of available keys.
 */
function drush_key_list() {
  $result = [];

  /* @var $key Drupal\key\Entity\Key */
  $keys = \Drupal::service('key.repository')->getKeys();

  // Filter by key type, if specified.
  if (drush_get_option('key-type')) {
    $key_type_filter = StringUtils::csvToArray(drush_get_option('key-type'));
    foreach ($keys as $id => $key) {
      if (!in_array($key->getKeyType()->getPluginId(), $key_type_filter)) {
        unset($keys[$id]);
      }
    }
  }

  // Filter by key provider, if specified.
  if (drush_get_option('key-provider')) {
    $key_provider_filter = StringUtils::csvToArray(drush_get_option('key-provider'));
    foreach ($keys as $id => $key) {
      if (!in_array($key->getKeyProvider()->getPluginId(), $key_provider_filter)) {
        unset($keys[$id]);
      }
    }
  }

  foreach ($keys as $id => $key) {
    $row = [];
    $row['id'] = $id;
    $row['label'] = $key->label();
    $row['key_type'] = $key->getKeyType()->getPluginDefinition()['label'];
    $row['key_provider'] = $key->getKeyProvider()->getPluginDefinition()['label'];
    $result[$id] = $row;
  }

  return $result;
}
