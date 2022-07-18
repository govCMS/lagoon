<?php

namespace Drupal\google_analytics;

use Drupal\google_analytics\Constants\GoogleAnalyticsPatterns;

/**
 * Decorator class for Google Analytics accounts
 */
class GaAccount {

  /**
   * The Google Analytics Account.
   *
   * @var string
   */
  protected $account;

  public function __construct(string $account) {
    $this->account = $account;
  }

  /**
   * Return the account as a string.
   *
   * @return string
   */
  public function __toString() {
    return $this->account;
  }

  /**
   * Detects if there is a universal analytics account.
   *
   * If any account is UA, then this will return true.
   *
   * @return bool
   */
  public function isUniversalAnalyticsAccount() {
    if (preg_match(GoogleAnalyticsPatterns::GOOGLE_ANALYTICS_UA_MATCH, $this->account)) {
      return TRUE;
    }
    return FALSE;
  }
}