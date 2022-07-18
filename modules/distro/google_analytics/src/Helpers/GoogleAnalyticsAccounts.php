<?php

namespace Drupal\google_analytics\Helpers;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\google_analytics\Constants\GoogleAnalyticsPatterns;
use Drupal\google_analytics\GaAccount;

class GoogleAnalyticsAccounts {

  /**
   * Private Key Service for generating user id hash.
   *
   * @var string
   */
  protected $privateKey;

  /**
   * The loaded config for the GA Module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The Google Analytics Accounts storage array.
   *
   * @var array
   */
  private $accounts;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PrivateKey $private_key) {
    $this->config = $config_factory->get('google_analytics.settings');

    $accounts = $this->config->get('account');
    // Create the accounts array from either a single gtag id or multiple ones.
    if (strpos($accounts, ',') === FALSE) {
      $this->accounts[] = new GaAccount($accounts);
    }
    else {
      $accounts_array = explode(',', $accounts);
      foreach($accounts_array as $account) {
        $this->accounts[] = new GaAccount($account);
      }
    }

    $this->privateKey = $private_key->get();
  }

  /**
   * Generate user id hash to implement USER_ID.
   *
   * The USER_ID value should be a unique, persistent, and non-personally
   * identifiable string identifier that represents a user or signed-in
   * account across devices.
   *
   * @param int $uid
   *   User id.
   *
   * @return string
   *   User id hash.
   */
  public function getUserIdHash($uid) {
    return Crypt::hmacBase64($uid, $this->privateKey . Settings::getHashSalt());
  }

  /**
   * Get the default measurement ID. Defaults to the first account in config.
   *
   * @return false|mixed|string
   */
  public function getDefaultMeasurementId() {
    // The top UA- or G- Account is the default measurement ID.
    foreach ($this->accounts as $account) {
      if (preg_match(GoogleAnalyticsPatterns::GOOGLE_ANALYTICS_TRACKING_MATCH, $account)) {
        return $account;
      }
    }
    return FALSE;
  }

  /**
   * Get accounts that aren't the default measurement ID.
   *
   * @return array|false|string[]
   */
  public function getAdditionalAccounts() {
    return array_filter($this->accounts, function($v) {
      return $v !== $this->getDefaultMeasurementId();
    });
  }

  /**
   * Return all the GA accounts stored.
   *
   * @return array|false|string[]
   */
  public function getAccounts() {
    return $this->accounts;
  }
}