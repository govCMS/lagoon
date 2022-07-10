<?php

/**
 * @file
 * Hooks specific to the Config Ignore module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of config entities that should be ignored.
 */
function hook_config_ignore_settings_alter(array &$settings) {
  $settings[] = 'system.site';
  $settings[] = 'field.*';
}

/**
 * @} End of "addtogroup hooks".
 */
