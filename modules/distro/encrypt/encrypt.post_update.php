<?php

/**
 * @file
 * Post update functions for Encrypt.
 */

/**
 * Force cache clear to ensure uninstall validator service is removed.
 *
 * @see https://www.drupal.org/project/encrypt/issues/2899478
 */
function encrypt_post_update_remove_uninstall_validator_service() {
  // Empty post-update hook.
}
