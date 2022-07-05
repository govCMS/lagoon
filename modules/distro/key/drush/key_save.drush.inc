<?php

/**
 * @file
 * Save a key.
 */

use Drupal\Component\Serialization\Json;

/**
 * Save a key.
 *
 * @param string $id
 *   The ID of the key to save.
 * @param string|null $key_value
 *   A key value to save.
 *
 * @return bool
 *   FALSE if not successful.
 */
function drush_key_save($id, $key_value = NULL) {
  $values = [];
  $values['id'] = $id;

  // Look for a key with the specified ID. If one exists, and the overwrite
  // option was not specified, set an error and abort.
  $existing_key = \Drupal::service('key.repository')->getKey($values['id']);
  $overwrite = drush_get_option('overwrite');
  if ($existing_key && !$overwrite) {
    return drush_set_error('DRUSH_KEY_EXISTS', dt('Key !id exists; specify --overwrite to overwrite.', ['!id' => $values['id']]));
  }

  // Add a warning about overwriting a key.
  if ($existing_key) {
    \Drupal::logger('key')->log('warning', (dt('Be extremely careful when overwriting a key! It may result in losing access to a service or making encrypted data unreadable.')));
  }

  // Confirm that the key should be saved.
  drush_print(dt('The following key will be saved: !id', ['!id' => $values['id']]));
  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }

  // Set any values defined as options.
  foreach (array_keys(key_drush_save_options()) as $option) {
    $value = drush_get_option($option, NULL);
    if (isset($value)) {
      if (in_array($option, [
        'key-type-settings',
        'key-provider-settings',
        'key-input-settings',
      ])) {
        $values[str_replace('-', '_', $option)] = Json::decode($value);
      }
      else {
        $values[str_replace('-', '_', $option)] = $value;
      }
    }
  }

  // If the label was not defined, use the ID.
  if (!isset($values['label'])) {
    $values['label'] = $values['id'];
  }

  // If the key already exists, make a clone and update it.
  // Otherwise, create a new key entity.
  if ($existing_key) {
    $key = clone $existing_key;
    foreach ($values as $index => $value) {
      if ($index != 'id') {
        $key->set($index, $value);
      }
    }
  }
  else {
    $storage = \Drupal::service('entity_type.manager')->getStorage('key');
    $key = $storage->create($values);
  }

  // If a key value was specified, set it.
  if (isset($key_value)) {
    $key->setKeyValue($key_value);
  }

  // Save the key.
  $key->save();

  // Load the key to confirm that it was saved.
  $key_check = \Drupal::service('key.repository')->getKey($values['id']);

  if (!$key_check) {
    return drush_set_error('DRUSH_KEY_NOT_SAVED', dt('Key !id was not saved.', ['!id' => $values['id']]));
  }

  \Drupal::logger('key')->log('success', (dt('Key !id was saved successfully.', ['!id' => $values['id']])));
}
