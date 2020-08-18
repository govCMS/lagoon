<?php

/**
 * @file
 * Optional include that enables Fast 404 logic.
 */

use Drupal\Core\Site\Settings;
use Drupal\fast404\Fast404;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fast 404 preboot.
 *
 * It's likely that the settings.php include will need refactoring when the
 * number of bootstrap phases is reduced and the kernel utilised more fully:
 * - https://drupal.org/node/2023495
 * - https://drupal.org/node/2016629
 *
 * @param array $settings
 *   The settings from settings.php.
 */
function fast404_preboot(array $settings = []) {

  // We must instantiate a Settings object before attempting to Settings::get().
  new Settings($settings);

  // Load the Fast404 class from within the module directory. Since modules
  // haven't been loaded at this point we cannot use the autoloader.
  require_once __DIR__ . '/src/Fast404.php';

  // Create a request object so we have something to pass Fast404.
  $request = Request::createFromGlobals();

  // This is pretty much a wrote copy of the Fast404EventSubscriber class that
  // fires when the module is enabled.
  $fast_404 = new Fast404($request);

  $fast_404->extensionCheck();
  if ($fast_404->isPathBlocked()) {
    $fast_404->response();
  }

  $fast_404->pathCheck();
  if ($fast_404->isPathBlocked()) {
    $fast_404->response();
  }
}
