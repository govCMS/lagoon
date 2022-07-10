<?php

/**
 * @file
 * Drush 8 commands for the Configuration Update Reports module.
 */

use Drupal\config_update_ui\Logger\ConfigUpdateUiDrush8Logger;

/**
 * Implements hook_drush_command().
 */
function config_update_ui_drush_command() {

  $items = [];

  $items['config-list-types'] = [
    'description' => 'List config types',
    'aliases' => ['clt'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-added-report'] = [
    'description' => 'Display a list of config items that did not come from your installed modules, themes, or install profile',
    'arguments' => [
      'name' => 'The type of config to report on. See config-list-types to list them. You can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-added-report action' => 'Displays the added config report for action config.',
    ],
    'aliases' => ['cra'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-missing-report'] = [
    'description' => 'Display a list of config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-missing-report type action' => 'Displays the missing config report for action config.',
    ],
    'aliases' => ['crm'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-inactive-report'] = [
    'description' => 'Display a list of optional config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-inactive-report type action' => 'Displays the inactive config report for action config.',
    ],
    'aliases' => ['cri'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-different-report'] = [
    'description' => 'Display a list of config items that differ from the versions provided by your installed modules, themes, or install profile. See config-diff to show what the differences are.',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-different-report type action' => 'Displays the differing config report for action config.',
    ],
    'aliases' => ['crd'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-diff'] = [
    'description' => 'Display line-by-line differences for one config item between your active config and the version currently being provided by an installed module, theme, or install profile',
    'arguments' => [
      'name' => 'The config item to diff. See config-different-report to list config items that are different.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-diff block.block.bartik_search' => 'Displays the config differences for the search block in the Bartik theme.',
    ],
    'aliases' => ['cfd'],
    'core' => ['8+'],
  ];

  $items['config-revert'] = [
    'description' => 'Revert one config item in active storage to the version provided by an installed module, theme, or install profile.',
    'arguments' => [
      'name' => 'The config item to revert. See config-different-report to list config items that are different.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-revert block.block.bartik_search' => 'Revert the config for the search block in the Bartik theme to the version provided by the install profile.',
    ],
    'aliases' => ['cfr'],
    'core' => ['8+'],
  ];

  $items['config-import-missing'] = [
    'description' => 'Import a missing or inactive config item provided by an installed module, theme, or install profile. Be sure that requirements are met.',
    'arguments' => [
      'name' => 'The name of the config item to import (usually the ID you would see in the user interface). See config-missing-report to list config items that are missing, and config-inactive-report to list config items that are inactive.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-import-missing block.block.bartik_search' => 'Import the config for the search block in the Bartik theme from the version provided by the install profile.',
    ],
    'aliases' => ['cfi'],
    'core' => ['8+'],
  ];

  $items['config-revert-multiple'] = [
    'description' => 'Revert a set of config items to the versions provided by installed modules, themes, or install profiles. A set is all differing items from one extension, or one type of configuration.',
    'arguments' => [
      'type' => 'Type of set to revert: "module" for all items from a module, "theme" for all items from a theme, "profile" for all items from the install profile, or "type" for all items of one config entity type. See config-different-report to list config items that are different.',
      'name' => 'The machine name of the module, theme, etc. to revert items of. All items in the corresponding config-different-report will be reverted.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-revert-multiple type action' => 'Revert all differing config items of type action.',
    ],
    'aliases' => ['cfrm'],
    'core' => ['8+'],
  ];

  return $items;
}

/**
 * Lists available config types.
 *
 * @return array
 *   An array of configuration types.
 */
function drush_config_update_ui_config_list_types() {
  return drush_config_update_ui_cli_service()->listTypes();
}

/**
 * Runs the config added report.
 *
 * @param string $name
 *   Type of config to report on.
 *
 * @return array
 *   An array of added configuration items.
 */
function drush_config_update_ui_config_added_report($name) {
  return drush_config_update_ui_cli_service()->addedReport($name);
}

/**
 * Runs the config missing report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 *
 * @return array
 *   An array of missing configuration items.
 */
function drush_config_update_ui_config_missing_report($type, $name) {
  return drush_config_update_ui_cli_service()->missingReport($type, $name);
}

/**
 * Runs the config inactive report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 *
 * @return array
 *   An array of inactive configuration items.
 */
function drush_config_update_ui_config_inactive_report($type, $name) {
  return drush_config_update_ui_cli_service()->inactiveReport($type, $name);
}

/**
 * Runs the config different report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 *
 * @return array
 *   An array of differing configuration items.
 */
function drush_config_update_ui_config_different_report($type, $name) {
  return drush_config_update_ui_cli_service()->differentReport($type, $name);
}

/**
 * Runs the drush config-diff command.
 *
 * @param string $name
 *   Config item to diff.
 *
 * @return string
 *   The formatted diff output.
 */
function drush_config_update_ui_config_diff($name) {
  return drush_config_update_ui_cli_service()->diff($name);
}

/**
 * Runs the drush config-revert command.
 *
 * @param string $name
 *   Config item to revert.
 */
function drush_config_update_ui_config_revert($name) {
  return drush_config_update_ui_cli_service()->revert($name);
}

/**
 * Runs the drush config-import-missing command.
 *
 * @param string $name
 *   Name of config item to import, without prefix.
 */
function drush_config_update_ui_config_import_missing($name) {
  return drush_config_update_ui_cli_service()->importMissing($name);
}

/**
 * Runs the drush config-revert-multiple command.
 *
 * @param string $type
 *   Type of config to revert: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of type to revert.
 */
function drush_config_update_ui_config_revert_multiple($type, $name) {
  return drush_config_update_ui_cli_service()->revertMultiple($type, $name);
}

/**
 * Instantiates and returns the Drush Commands CLI service for Drush 8.
 */
function drush_config_update_ui_cli_service() {
  /** @var \Drupal\config_update_ui\ConfigUpdateUiCliService $config_update_ui_service */
  $config_update_ui_service = \Drupal::service('config_update_ui.cli');
  $logger = new ConfigUpdateUiDrush8Logger(\Drupal::service('logger.log_message_parser'));
  $config_update_ui_service->setLogger($logger);

  return $config_update_ui_service;
}
