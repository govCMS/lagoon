<?php

namespace Drupal\google_analytics\Constants;

/**
 * Defines events for the google_analytics module.
 */
final class GoogleAnalyticsEvents {

  /**
   * The event fired to build the Google Analytics javascript.
   *
   * Each action in Drupal that is tracked with google Analytics should have its
   * own event subscriber to compile into the final javascript.
   *
   * @Event
   *
   * @see \Drupal\google_analytics\Event\BuildGaJavascriptEvent
   *
   * @var string
   */
  const BUILD_JAVASCRIPT = 'google_analytics_build_javascript';

  /**
   * The event fired to build the Google Analytics javascript.
   *
   * Each action in Drupal that is tracked with google Analytics should have its
   * own event subscriber to compile into the final javascript.
   *
   * @Event
   *
   * @see \Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent
   *
   * @var string
   */
  const ADD_EVENT = 'google_analytics_add_event';

  /**
   * The event fired to build the Google Analytics javascript.
   *
   * Each action in Drupal that is tracked with google Analytics should have its
   * own event subscriber to compile into the final javascript.
   *
   * @Event
   *
   * @see \Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent
   *
   * @var string
   */
  const ADD_CONFIG = 'google_analytics_add_config';

  /**
   * The event fired to set custom page paths.
   *
   * Each built in page path should stop propigation once it is found.
   * This will then set the custom page path in analytics.
   *
   * @Event
   *
   * @see \Drupal\google_analytics\Event\PagePathEvent
   *
   * @var string
   */
  const PAGE_PATH = 'google_analytics_page_path';
}
