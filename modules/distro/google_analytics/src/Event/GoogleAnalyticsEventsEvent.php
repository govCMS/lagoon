<?php

namespace Drupal\google_analytics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\google_analytics\GaJavascriptObject;

/**
 * Event that is fired when a user logs in.
 */
class GoogleAnalyticsEventsEvent extends Event {

  /**
   * The GA Javascript Object for which to create events.
   *
   * @var \Drupal\google_analytics\GaJavascriptObject
   */
  protected $javascript;

  /**
   * GoogleAnalyticsEventsEvent constructor.
   *
   * @param \Drupal\google_analytics\GaJavascriptObject $javascript
   *   The GA Javascript object.
   */
  public function __construct(GaJavascriptObject $javascript) {
    $this->javascript = $javascript;
  }

  /**
   * Get the GA Javascript Object being created.
   *
   * @return array
   *   Events in the javascript.
   */
  public function getEvents() {
    return $this->javascript->getEvents();
  }

  /**
   * Get the GA Javascript Object being created.
   */
  public function addEvent($event) {
    $this->javascript->addEvent($event);
  }

}
