<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsEvents;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent;
use Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class GoogleAnalyticsEventBase.
 *
 * Base class create events for Google Analytics.
 *
 * @package Drupal\google_analytics\EventSubscriber\GoogleAnalyticsEvents
 */
abstract class GoogleAnalyticsEventBase implements EventSubscriberInterface {

  /**
   * Google Analytics Config
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $ga_config;

  /**
   * Priority of the subscriber.
   *
   * @var int
   */
  public static $priority = 0;

  /**
   * Detect Legacy Universal Analytics Accounts
   *
   * @var bool
   */
  protected $isLegacy;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   * @param \Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts $ga_accounts
   *   The Google Analytics Account Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoogleAnalyticsAccounts $ga_accounts) {
    $this->ga_config = $config_factory->get('google_analytics.settings');
    $this->isLegacy = $ga_accounts->getDefaultMeasurementId()->isUniversalAnalyticsAccount();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::ADD_EVENT][] =
      ['onAddEvent', self::$priority];
    return $events;
  }

  /**
   * Adds a new event in an array format for UA or GA4.
   *
   * @return array
   */
  abstract public function addGaEvent(): array;

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent $event
   *   The event being dispatched.
   */
  public function onAddEvent(GoogleAnalyticsEventsEvent $event) {
    $ga_events = $this->addGaEvent();
    if (!empty($ga_events)) {
      foreach($ga_events AS $ga_event) {
        $event->addEvent($ga_event);
      }
    }
  }
}
