<?php

/**
 * @file
 * GovCMS8 foundations update helper functions.
 */

 //use Drupal\Core\Extension\ExtensionLifecycle;

/**
 * Implements hook_update_N().
 */
function govcms8_foundations_update_10001() {
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('govcms8_foundations')) {
    \Drupal::service('module_installer')->uninstall(['govcms8_foundations', 'govcms8_calendar_item', 'govcms8_layouts',
      'govcms8_modifiers']);
  }
}
