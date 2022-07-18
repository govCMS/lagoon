<?php

namespace Drupal\google_analytics\EventSubscriber\PagePath;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds Content Translation to custom URL
 */
class InvalidUserLogin implements EventSubscriberInterface {

  /**
   * @var \GuzzleHttp\Psr7\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(RequestStack $request, CurrentRouteMatch $current_route) {
    $this->request = $request->getCurrentRequest();
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::PAGE_PATH][] = ['onPagePath', 100];
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
    // #2693595: User has entered an invalid login and clicked on forgot
    // password link. This link contains the username or email address and may
    // get send to Google if we do not override it. Override only if 'name'
    // query param exists. Last custom url condition, this need to win.
    //
    // URLs to protect are:
    // - user/password?name=username
    // - user/password?name=foo@example.com
    $base_path = base_path();
    if ($this->currentRoute->getRouteName() == 'user.pass' && $this->request->query->has('name')) {
      $event->setPagePath('"' . $base_path . 'user/password"');
      $event->stopPropagation();
    }
  }
}
