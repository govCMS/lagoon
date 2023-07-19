<?php

/**
 * @file
 * Drush integration for vem_migrate_oembed.
 */

/**
 * Implements hook_drush_command().
 */
function vem_migrate_oembed_drush_command() {
  $items['vem_migrate_oembed'] = [
    'description' => 'Migrates from VEF to core media.',
    'aliases' => ['vemmo'],
    'core' => ['8+'],
    'examples' => [
      "drush vemmo" => "Migrates from VEF to core media.",
    ],
  ];

  return $items;
}

/**
 * Callback for drush commmand "video-embed-media-migrate-oembed" (vemmo).
 */
function drush_vem_migrate_oembed_vem_migrate_oembed() {
  /** @var \Drupal\vem_migrate_oembed\VemMigrate $migrate */
  $migrate = \Drupal::service('vem_migrate_oembed.migrate');
  $migrate->migrate();
  \drush_print(\dt('Migration complete.'));
}
