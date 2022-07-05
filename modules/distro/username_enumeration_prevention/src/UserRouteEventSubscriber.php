<?php

namespace Drupal\username_enumeration_prevention;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modifies user-related routes to respond with 404 rather than 403.
 *
 * @package Drupal\username_enumeration_prevention
 */
class UserRouteEventSubscriber implements EventSubscriberInterface {

  /**
   * Cache CID with user route IDS.
   */
  const ROUTE_CID = 'username_enumeration_prevention_user_route_ids';

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteProviderInterface $routeProvider, EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->routeProvider = $routeProvider;
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function onException(\Symfony\Component\HttpKernel\Event\ExceptionEvent $event) {
    $routeMatch = RouteMatch::createFromRequest($event->getRequest());
    if ($event->getThrowable() instanceof AccessDeniedHttpException && in_array($routeMatch->getRouteName(), $this->getUserRoutes())) {
      $event->setThrowable(new NotFoundHttpException());
    }
  }

  /**
   * Get an array of user route IDs.
   *
   * @return array
   *   An array of user route IDs.
   */
  protected function getUserRoutes(): array {
    $userRouteIds = $this->cache->get(static::ROUTE_CID);
    if ($userRouteIds !== FALSE) {
      return $userRouteIds->data;
    }

    $userLinkTemplates = $this->entityTypeManager
      ->getDefinition('user')
      ->getLinkTemplates();

    $routes = new RouteCollection();
    foreach ($userLinkTemplates as $path) {
      $routes->addCollection($this->routeProvider->getRoutesByPattern($path));
    }

    $userRouteIds = array_keys(array_filter(iterator_to_array($routes), function (Route $route): bool {
      $parameters = $route->getOption('parameters') ?? [];
      if (is_array($parameters)) {
        foreach ($parameters as $parameter) {
          // This captures most routes, however some legacy routes don't have
          // parameters, especially views.
          if ($parameter['type'] ?? NULL === 'entity:user') {
            return TRUE;
          }
        }
      }

      return strpos($route->getPath(), '{user}') !== FALSE;
    }));

    $userRouteIds[] = 'user.cancel_confirm';
    $userRouteIds[] = 'shortcut.set_switch';
    $this->cache->set(static::ROUTE_CID, $userRouteIds, Cache::PERMANENT, ['routes']);
    return $userRouteIds;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION] = 'onException';
    return $events;
  }

}
