<?php

namespace Drupal\config_update_ui;

use Drupal\config_update\ConfigDiffer;
use Drupal\config_update\ConfigListerWithProviders;
use Drupal\config_update\ConfigReverter;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles all the logic for commands for various versions of Drush.
 *
 * To use this class, you must call the
 * \Drupal\config_update_ui\ConfigUpdateUiCliService::setLogger() method before
 * doing anything else.
 */
class ConfigUpdateUiCliService {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The config differ.
   *
   * @var \Drupal\config_update\ConfigDiffer
   */
  protected $configDiff;

  /**
   * The config lister.
   *
   * @var \Drupal\config_update\ConfigListerWithProviders
   */
  protected $configList;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigReverter
   */
  protected $configUpdate;

  /**
   * The logger class.
   *
   * This differs between Drush version 8 and 9.
   *
   * @var \Drush\Log\Logger|\Drupal\config_update_ui\ConfigUpdateUiDrush8Logger
   */
  protected $logger;

  /**
   * Constructs a ConfigUpdateUiCliService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager.
   * @param \Drupal\config_update\ConfigDiffer $configDiff
   *   The config differ.
   * @param \Drupal\config_update\ConfigListerWithProviders $configList
   *   The config lister.
   * @param \Drupal\config_update\ConfigReverter $configUpdate
   *   The config reverter.
   */
  public function __construct(EntityTypeManagerInterface $entityManager, ConfigDiffer $configDiff, ConfigListerWithProviders $configList, ConfigReverter $configUpdate) {
    $this->entityManager = $entityManager;
    $this->configDiff = $configDiff;
    $this->configList = $configList;
    $this->configUpdate = $configUpdate;
  }

  /**
   * Lists config types.
   *
   * @return array|\Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   If using Drush 8, an array of configuration types. If using Drush 9, a
   *   structured data object of rows of configuration types.
   */
  public function listTypes() {
    $rows = [];
    $definitions = $this->configList->listTypes();
    $output = array_keys($definitions);

    return $output;
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
   * @return array|\Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   If using Drush 8, an array of added configuration. If using Drush 9, a
   *   structured data object of rows of added configuration.
   */
  public function addedReport($name) {
    list($activeList, $installList, $optionalList) = $this->configList->listConfig('type', $name);
    $addedItems = array_diff($activeList, $installList, $optionalList);
    if (!count($addedItems)) {
      $this->logger->success(dt('No added config.'));
    }
    sort($addedItems);

    return $addedItems;
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
   * @return array|\Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   If using Drush 8, an array of missing configuration. If using Drush 9, a
   *   structured data object of rows of missing configuration.
   */
  public function missingReport($type, $name) {
    list($activeList, $installList, $optionalList) = $this->configList->listConfig($type, $name);
    $missingItems = array_diff($installList, $activeList);
    if (!count($missingItems)) {
      $this->logger->success(dt('No missing config.'));
    }
    sort($missingItems);

    return $missingItems;
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
   * @return array|\Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   If using Drush 8, an array of inactive configuration. If using Drush 9, a
   *   structured data object of rows of inactive configuration.
   */
  public function inactiveReport($type, $name) {
    list($activeList, $installList, $optionalList) = $this->configList->listConfig($type, $name);
    $inactiveItems = array_diff($optionalList, $activeList);
    if (!count($inactiveItems)) {
      $this->logger->success(dt('No inactive config.'));
    }
    sort($inactiveItems);

    return $inactiveItems;
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
   * @return array|\Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   If using Drush 8, an array of differing configuration. If using Drush 9,
   *   a structured data object of rows of differing configuration.
   */
  public function differentReport($type, $name) {
    $differentItems = $this->getDifferentItems($type, $name);
    if (!count($differentItems)) {
      $this->logger->success(dt('No different config'));
    }

    return $differentItems;
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
   */
  public function diff($name) {
    $extension = $this->configUpdate->getFromExtension('', $name);
    $active = $this->configUpdate->getFromActive('', $name);
    if ($extension && $active) {
      $diff = $this->configDiff->diff($extension, $active);
      // Drupal\Component\Diff\DiffFormatter does not expose a service so we
      // instantiate it manually here.
      $diffFormatter = new DiffFormatter();
      $output = $diffFormatter->format($diff);
      return $output;
    }
    else {
      $this->logger->error(dt('Config is missing, cannot diff.'));
    }
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
   */
  public function revert($name) {
    $type = $this->configList->getTypeNameByConfigName($name);
    // The lister gives NULL if simple configuration, but the reverter expects
    // 'system.simple' so we convert it.
    if ($type === NULL) {
      $type = 'system.simple';
    }
    $shortname = $this->getConfigShortname($type, $name);
    if ($this->configUpdate->revert($type, $shortname)) {
      $this->logger->success(dt('The configuration item @name was reverted to its source.', ['@name' => $name]));
    }
    else {
      $this->logger->error(dt('There was an error and the configuration item @name was not reverted.', ['@name' => $name]));
    }
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
   */
  public function importMissing($name) {
    $type = $this->configList->getTypeNameByConfigName($name);
    // The lister gives NULL if simple configuration, but the reverter expects
    // 'system.simple' so we convert it.
    if ($type === NULL) {
      $type = 'system.simple';
    }
    $shortname = $this->getConfigShortname($type, $name);
    if ($this->configUpdate->import($type, $shortname)) {
      $this->logger->success(dt('The configuration item @name was imported from its source.', ['@name' => $name]));
    }
    else {
      $this->logger->error(dt('There was an error and the configuration item @name was not imported.', ['@name' => $name]));
    }
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
   */
  public function revertMultiple($type, $name) {
    $different = $this->getDifferentItems($type, $name);
    foreach ($different as $name) {
      $this->revert($name);
    }
  }

  /**
   * Registers a logger and sets the Drush version.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logging object to use. For Drush 8, use an object of class
   *   \Drupal\config_ui\ConfigUpdateUiDrush8Logger (or a subclass). For Drush
   *   9 and later, use the output of \Drush\Drush::logger().
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Gets the current logging object.
   *
   * @return \Psr\Log\LoggerInterface
   *   The current logging object.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Lists differing config items.
   *
   * Lists config items that differ from the versions provided by your
   * installed modules, themes, or install profile. See config-diff to show
   * what the differences are.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return array
   *   An array of differing configuration items.
   */
  protected function getDifferentItems($type, $name) {
    list($activeList, $installList, $optionalList) = $this->configList->listConfig($type, $name);
    $addedItems = array_diff($activeList, $installList, $optionalList);
    $activeAndAddedItems = array_diff($activeList, $addedItems);
    $differentItems = [];
    foreach ($activeAndAddedItems as $name) {
      $active = $this->configUpdate->getFromActive('', $name);
      $extension = $this->configUpdate->getFromExtension('', $name);
      if (!$this->configDiff->same($active, $extension)) {
        $differentItems[] = $name;
      }
    }
    sort($differentItems);

    return $differentItems;
  }

  /**
   * Gets the config item shortname given the type and name.
   *
   * @param string $type
   *   The type of the config item.
   * @param string $name
   *   The name of the config item.
   *
   * @return string
   *   The shortname for the configuration item.
   */
  protected function getConfigShortname($type, $name) {
    $shortname = $name;
    if ($type != 'system.simple') {
      $definition = $this->entityManager->getDefinition($type);
      $prefix = $definition->getConfigPrefix() . '.';
      if (strpos($name, $prefix) === 0) {
        $shortname = substr($name, strlen($prefix));
      }
    }

    return $shortname;
  }

}
