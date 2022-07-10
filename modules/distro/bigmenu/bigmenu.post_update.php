<?php

/**
 * @file
 * The bigmenu post update hook file.
 */

/**
 * Install new bigmenu settings.
 */
function bigmenu_post_update_update_settings() {
  $config = \Drupal::configFactory()->getEditable('bigmenu.settings');
  if ($config->get('max_depth') === NULL) {
    $config->set('max_depth', 1)->save(TRUE);
  }
}
