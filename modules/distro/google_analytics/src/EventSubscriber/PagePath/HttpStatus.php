<?php

namespace Drupal\google_analytics\EventSubscriber\PagePath;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds Content Translation to custom URL
 */
class HttpStatus implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var \GuzzleHttp\Psr7\Request
   */
  protected $request;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::PAGE_PATH][] = ['onPagePath'];
    return $events;
  }

  /**
   * Adds error pages to the page path.
   *
   * @param \Drupal\google_analytics\Event\PagePathEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPagePath(PagePathEvent $event) {
    // Get page http status code for visibility filtering.
    $status = NULL;
    if ($exception = $this->request->attributes->get('exception')) {
      $status = $exception->getStatusCode();
    }
    // TODO: Make configurable
    $trackable_status_codes = [
      // "Forbidden" status code.
      '403',
      // "Not Found" status code.
      '404',
    ];
    if (in_array($status, $trackable_status_codes)) {
      $base_path = base_path();

      // Track access denied (403) and file not found (404) pages.
      $event->setPagePath('"' . $base_path . $status . '.html?page=" + document.location.pathname + document.location.search + "&from=" + document.referrer');
      $event->stopPropagation();
    }
  }
}
