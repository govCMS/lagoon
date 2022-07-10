<?php

namespace Drupal\config_update_ui\Commands;

use Drush\Drush;
use Drush\Commands\DrushCommands;
use Drupal\config_update_ui\ConfigUpdateUiCliService;

/**
 * A set of Drush commands for Config Update Manager.
 */
class ConfigUpdateUiCommands extends DrushCommands {

  /**
   * The interoperability CLI service for Configuration Update Manager.
   *
   * Allows for sharing logic for CLI commands between Drush 8 and 9.
   *
   * @var \Drupal\config_update_ui\ConfigUpdateUiCliService
   */
  protected $cliService;

  /**
   * Constructs a ConfigUpdateUiCommands object.
   *
   * @param \Drupal\config_update_ui\ConfigUpdateUiCliService $cliService
   *   The CLI service which allows interoperability between Drush versions.
   */
  public function __construct(ConfigUpdateUiCliService $cliService) {
    $this->cliService = $cliService;
    $this->cliService->setLogger(Drush::logger());
  }

  /**
   * Lists config types.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of configuration types.
   *
   * @command config:list-types
   * @aliases clt,config-list-types
   */
  public function listTypes() {
    return $this->cliService->listTypes();
  }

  /**
   * Displays added config items.
   *
   * Displays a list of config items that did not come from your installed
   * modules, themes, or install profile.
   *
   * @param string $name
   *   The type of config to report on. See config-list-types to list them.
   *   You can also use system.all for all types, or system.simple for simple
   *   config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of added configuration items.
   *
   * @usage drush config-added-report action
   *   Displays the added config report for action config.
   *
   * @command config:added-report
   * @aliases cra,config-added-report
   */
  public function addedReport($name) {
    return $this->cliService->addedReport($name);
  }

  /**
   * Displays missing config items.
   *
   * Displays a list of config items from your installed modules, themes, or
   * install profile that are not currently in your active config.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of missing configuration items.
   *
   * @usage drush config-missing-report type action
   *   Displays the missing config report for action config.
   *
   * @command config:missing-report
   * @aliases crm,config-missing-report
   */
  public function missingReport($type, $name) {
    return $this->cliService->missingReport($type, $name);
  }

  /**
   * Displays optional config items.
   *
   * Displays a list of optional config items from your installed modules,
   * themes, or install profile that are not currently in your active config.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of inactive configuration items.
   *
   * @usage drush config-inactive-report type action
   *   Displays the inactive config report for action config.
   *
   * @command config:inactive-report
   * @aliases cri,config-inactive-report
   */
  public function inactiveReport($type, $name) {
    return $this->cliService->inactiveReport($type, $name);
  }

  /**
   * Displays differing config items.
   *
   * Displays a list of config items that differ from the versions provided by
   * your installed modules, themes, or install profile. See config-diff to
   * show what the differences are.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of differing configuration items.
   *
   * @usage drush config-different-report type action
   *   Displays the differing config report for action config.
   *
   * @command config:different-report
   * @aliases crd,config-different-report
   */
  public function differentReport($type, $name) {
    return $this->cliService->differentReport($type, $name);
  }

  /**
   * Displays a diff of a config item.
   *
   * Displays line-by-line differences for one config item between your active
   * config and the version currently being provided by an installed module,
   * theme, or install profile.
   *
   * @param string $name
   *   The config item to diff. See config-different-report to list config
   *   items that are different.
   *
   * @return string
   *   The formatted diff output.
   *
   * @usage drush config-diff block.block.bartik_search
   *   Displays the config differences for the search block in the Bartik theme.
   *
   * @command config:diff
   * @aliases cfd,config-diff
   */
  public function diff($name) {
    return $this->cliService->diff($name);
  }

  /**
   * Reverts a config item.
   *
   * Reverts one config item in active storage to the version provided by an
   * installed module, theme, or install profile.
   *
   * @param string $name
   *   The config item to revert. See config-different-report to list config
   *   items that are different.
   *
   * @usage drush config-revert block.block.bartik_search
   *   Revert the config for the search block in the Bartik theme to the
   *   version provided by the install profile.
   *
   * @command config:revert
   * @aliases cfr,config-revert
   */
  public function revert($name) {
    $this->cliService->revert($name);
  }

  /**
   * Imports missing config item.
   *
   * Imports a missing or inactive config item provided by an installed module,
   * theme, or install profile. Be sure that requirements are met.
   *
   * @param string $name
   *   The name of the config item to import (usually the ID you would see in
   *   the user interface). See config-missing-report to list config items that
   *   are missing, and config-inactive-report to list config items that are
   *   inactive.
   *
   * @usage drush config-import-missing block.block.bartik_search
   *   Import the config for the search block in the Bartik theme from the
   *   version provided by the install profile.
   *
   * @command config:import-missing
   * @aliases cfi,config-import-missing
   */
  public function importMissing($name) {
    $this->cliService->importMissing($name);
  }

  /**
   * Reverts multiple config items to extension provided version.
   *
   * Reverts a set of config items to the versions provided by installed
   * modules, themes, or install profiles. A set is all differing items from
   * one extension, or one type of configuration.
   *
   * @param string $type
   *   Type of set to revert: "module" for all items from a module, "theme" for
   *   all items from a theme, "profile" for all items from the install profile,
   *   or "type" for all items of one config entity type. See
   *   config-different-report to list config items that are different.
   * @param string $name
   *   The machine name of the module, theme, etc. to revert items of. All
   *   items in the corresponding config-different-report will be reverted.
   *
   * @usage drush config-revert-multiple type action
   *   Revert all differing config items of type action.
   *
   * @command config:revert-multiple
   * @aliases cfrm,config-revert-multiple
   */
  public function revertMultiple($type, $name) {
    return $this->cliService->revertMultiple($type, $name);
  }

}
