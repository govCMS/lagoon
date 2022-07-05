<?php

/**
 * @file
 * Delete a key.
 */

/**
 * Delete a key.
 *
 * @param string $id
 *   The ID of the key to delete.
 *
 * @return bool
 *   FALSE if not successful.
 */
function drush_key_delete($id) {
  // Look for a key with the specified ID. If one does not exist, set an
  // error and abort.
  /* @var $key \Drupal\key\Entity\Key */
  $key = \Drupal::service('key.repository')->getKey($id);
  if (!$key) {
    return drush_set_error('DRUSH_KEY_DOES_NOT_EXIST', dt('Key !id does not exist.', ['!id' => $id]));
  }

  // Confirm that the key should be deleted.
  \Drupal::logger('key')->log('warning', (dt('Be extremely careful when deleting a key! It may result in losing access to a service or making encrypted data unreadable.')));
  drush_print(dt('The following key will be deleted: !id', ['!id' => $id]));
  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }

  // Delete the key.
  $key->delete();

  // Try to load the key to confirm that it was deleted.
  $key_check = \Drupal::service('key.repository')->getKey($id);

  // If the key still exists, set an error and abort.
  if ($key_check) {
    return drush_set_error('DRUSH_KEY_NOT_DELETED', dt('Key !id was not deleted.', ['!id' => $id]));
  }

  \Drupal::logger('key')->log('success', (dt('Key !id was deleted successfully.', ['!id' => $id])));
}
