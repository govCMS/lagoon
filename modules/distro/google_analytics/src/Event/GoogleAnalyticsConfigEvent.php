<?php

namespace Drupal\google_analytics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\google_analytics\GaAccount;
use Drupal\google_analytics\GaJavascriptObject;

/**
 * Event that gathers all the config settings for a GA account.
 */
class GoogleAnalyticsConfigEvent extends Event {

  /**
   * The GA Javascript Object for which to store config.
   *
   * @var \Drupal\google_analytics\GaJavascriptObject
   */
  protected $javascript;

  /**
   * Array representing the config to pass to GA.
   *
   * @var array
   */
  protected $config;

  /**
   * Array representing the config to pass to GA.
   *
   * @var \Drupal\google_analytics\GaAccount
   */
  protected $gaAccount;

  /**
   * GoogleAnalyticsConfigEvent constructor.
   *
   * @param \Drupal\google_analytics\GaJavascriptObject $javascript
   *   The GA Javascript Object.
   */
  public function __construct(GaJavascriptObject $javascript, GaAccount $ga_account) {
    $this->javascript = $javascript;
    $this->gaAccount = $ga_account;
  }

  /**
   * Get the GA Javascript Object.
   *
   * @return \Drupal\google_analytics\GaJavascriptObject
   *   The GA Javascript
   */
  public function getJavascript() {
    return $this->javascript;
  }

  /**
   * Get the specific Google Analytics account associated with this config.
   *
   * @return \Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts
   */
  public function getGaAccount() {
    return $this->gaAccount;
  }

  /**
   * Get the GA Javascript Object being created.
   *
   * @return array
   *   Config to be set in the GA javascript
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Set a config key.
   *
   */
  public function addConfig($config_key, $value) {
    $this->config[$config_key] = $value;
  }

}
