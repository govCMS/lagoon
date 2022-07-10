<?php

/**
 * @file
 * Post update functions for Contact Storage.
 */

use Drupal\system\Entity\Action;

/**
 * Renames the "message_delete" action to avoid Message module conflicts.
 */
function contact_storage_post_update_rename_message_delete_action() {
  $action = Action::load('message_delete_action');
  if ($action) {
    $action->set('id', 'contact_message_delete_action')
      ->setPlugin('entity:delete_action:contact_message');
    $action->save();
  }
}
