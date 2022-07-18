<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds default config to Google Analytics.
 */
class DefaultConfig implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Current Drupal User Account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentAccount;

  /**
   * The Global Google Analytics Accounts Service
   *
   * @var \Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts
   */
  protected $gaAccounts;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoogleAnalyticsAccounts $ga_accounts, AccountProxyInterface $account) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->gaAccounts = $ga_accounts;
    $this->currentAccount = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::ADD_CONFIG][] = ['onAddConfig'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onAddConfig(GoogleAnalyticsConfigEvent $event) {
    $javascript = $event->getJavascript();
    $ga_account = $event->getGaAccount();

    // Custom Code Snippets that aren't created programmatically.
    $codesnippet_parameters = $this->config->get('codesnippet.create') ?? [];

    // Build the arguments fields list.
    // https://developers.google.com/analytics/devguides/collection/gtagjs/sending-data
    $arguments = ['groups' => 'default'];
    $arguments = array_merge($arguments, $codesnippet_parameters);

    // Domain tracking type.
    global $cookie_domain;
    $domain_mode = $this->config->get('domain_mode');

    // Per RFC 2109, cookie domains must contain at least one dot other than the
    // first. For hosts such as 'localhost' or IP Addresses we don't set a
    // cookie domain.
    if ($domain_mode == 1 && count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $arguments = array_merge($arguments, ['cookie_domain' => $cookie_domain]);
      $javascript->setAdsenseScript($cookie_domain);
    }
    elseif ($domain_mode == 2) {
      // Cross Domain tracking
      // https://developers.google.com/analytics/devguides/collection/gtagjs/cross-domain
      $arguments['linker'] = [
        'domains' => preg_split('/(\r\n?|\n)/', $this->config->get('cross_domains')),
      ];
      $javascript->setAdsenseScript();
    }

    // Track logged in users across all devices.
    if ($this->currentAccount->isAuthenticated()) {
      $arguments['user_id'] = $this->gaAccounts->getUserIdHash($this->currentAccount->id());
    }

    // Eliminate for GA 4.x
    if ($this->config->get('privacy.anonymizeip') && $ga_account->isUniversalAnalyticsAccount()) {
      $arguments['anonymize_ip'] = TRUE;
    }

    $page_path = new PagePathEvent();
    // Get the event_dispatcher service and dispatch the event.
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($page_path, GoogleAnalyticsEvents::PAGE_PATH);

    $path_type = $ga_account->isUniversalAnalyticsAccount() ? 'page_path' : 'page_location';
    $arguments['page_placeholder'] = 'PLACEHOLDER_' . $path_type;

    // TODO: Rewrite this into the PagePath event that executes first.
    if ($this->config->get('track.urlfragments')) {
      $arguments['page'] = 'location.pathname + location.search + location.hash';
    }

    if (!empty($page_path->getPagePath())) {
      $arguments['page'] = $page_path->getPagePath();
    }

    // Add enhanced link attribution after 'create', but before 'pageview' send.
    // @see https://developers.google.com/analytics/devguides/collection/gtagjs/enhanced-link-attribution
    if ($this->config->get('track.linkid')) {
      $arguments['link_attribution'] = TRUE;
    }

    // Disabling display features.
    // @see https://developers.google.com/analytics/devguides/collection/gtagjs/display-features
    if (!$this->config->get('track.displayfeatures')) {
      $arguments['allow_ad_personalization_signals'] = FALSE;
    }

    foreach ($arguments as $config_key => $value) {
      $event->addConfig($config_key, $value);
    }
  }
}
