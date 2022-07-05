<?php

/**
 * @file
 * Hooks specific to the Key module.
 */

/**
 * Alter the definitions of Key Provider plugins.
 *
 * This hook is invoked by KeyProviderManager::__construct().
 *
 * @param array $key_providers
 *   An array containing all of the key provider plugin definitions.
 */
function hook_key_provider_info_alter(array &$key_providers) {
  // Swap the classes used for the Configuration and File key providers.
  $key_providers['config']['class'] = 'Drupal\key\Plugin\KeyProvider\FileKeyProvider';
  $key_providers['file']['class'] = 'Drupal\key\Plugin\KeyProvider\ConfigKeyProvider';
}
