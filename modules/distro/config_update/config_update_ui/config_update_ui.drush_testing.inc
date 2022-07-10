<?php

/**
 * @file
 * Defines stubs for Drush functions and constants so tests can run.
 *
 * @see \Drupal\config_update_ui\Tests\ConfigUpdateTest
 */

if (!function_exists('drush_log')) {

  /**
   * Stub for drush_log() function for testing.
   */
  function drush_log() {
  }

}

if (!function_exists('drush_print')) {

  /**
   * Stub for drush_print() function for testing.
   */
  function drush_print() {
  }

}

if (!function_exists('dt')) {

  /**
   * Stub for dt() function for testing.
   */
  function dt($text) {
    return $text;
  }

}

if (!class_exists('Drush\Log\LogLevel')) {

  /**
   * Mock class for Drush\Log\LogLevel::SUCCESS and ERROR constants.
   *
   * Used in Drush 8's logger for the Config Update CLI service.
   */
  class DrushLogLevel {
    const SUCCESS = 'success';
    const ERROR = 'error';

  }
  class_alias('DrushLogLevel', 'Drush\Log\LogLevel');

}

defined('STDERR') or define('STDERR', 25);
