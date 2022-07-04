<?php

namespace Drupal\clamav;


class Config  {
  const MODE_DAEMON = 0;
  const MODE_EXECUTABLE = 1;
  const MODE_UNIX_SOCKET = 2;

  const OUTAGE_BLOCK_UNCHECKED = 0;
  const OUTAGE_ALLOW_UNCHECKED = 1;

  // Drupal read-only config object.
  protected $_config;


  /**
   * Constructor.
   *
   * Load the config from Drupal's CMI.
   */
  public function __construct() {
    $this->_config = \Drupal::config('clamav.settings');
  }

  // Global config options:
  public function enabled() {
    return $this->_config->get('enabled');
  }
  public function scan_mode() {
    return $this->_config->get('scan_mode');
  }
  public function outage_action() {
    return $this->_config->get('outage_action');
  }
  public function verbosity() {
    return $this->_config->get('verbosity');
  }

  public function get($name) {
    return $this->_config->get($name);
  }

}
