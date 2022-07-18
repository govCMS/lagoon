<?php

namespace Drupal\google_analytics\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user logs in.
 */
class PagePathEvent extends Event {

  /**
   * The Custom URL to be attached to the GA javascript.
   *
   * @var string
   */
  protected $page_path = '';

  /**
   * Get the current page path
   *
   * @return string
   *   The currently set custom url in the javascript.
   */
  public function getPagePath() {
    return $this->page_path;
  }

  /**
   * Get the GA Javascript Object being created.
   */
  public function setPagePath($url) {
    $this->page_path = $url;
  }

}
