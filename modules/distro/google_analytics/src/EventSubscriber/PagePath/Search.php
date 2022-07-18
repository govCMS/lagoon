<?php

namespace Drupal\google_analytics\EventSubscriber\PagePath;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds Drupal Messages to GA Javascript.
 */
class Search implements EventSubscriberInterface {

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
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

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
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request, ModuleHandler $module_handler, CurrentRouteMatch $current_route) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->request = $request->getCurrentRequest();
    $this->moduleHandler = $module_handler;
    $this->currentRoute = $current_route;

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::PAGE_PATH][] = ['onCustomPagePath'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\PagePathEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onCustomPagePath(PagePathEvent $event) {
    // Site search tracking support.
    if ($this->moduleHandler->moduleExists('search') && $this->config->get('track.site_search') && (strpos($this->currentRoute->getRouteName(), 'search.view') === 0) && $keys = ($this->request->query->has('keys') ? trim($this->request->get('keys')) : '')) {
      // hook_item_list__search_results() is not executed if search result is
      // empty. Make sure the counter is set to 0 if there are no results.
      $entity = $this->currentRoute->getParameter('entity');
      if (isset($entity)) {
        $entity_id = $entity->id();
        $url_custom = '(window.google_analytics_search_results) ? ' . Json::encode(Url::fromRoute('search.view_' . $entity_id, [], ['query' => ['search' => $keys]])
            ->toString()) . ' : ' . Json::encode(Url::fromRoute('search.view_' . $entity_id, [], [
              'query' => [
                'search' => 'no-results:' . $keys,
                'cat' => 'no-results'
              ]
          ])->toString());
        $event->setPagePath($url_custom);
        $event->stopPropagation();
      }
    }
  }
}
