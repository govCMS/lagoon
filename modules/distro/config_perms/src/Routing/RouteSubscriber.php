<?php

namespace Drupal\config_perms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\config_perms\Entity\CustomPermsEntity;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\config_perms\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $custom_perms = CustomPermsEntity::loadMultiple();
    /** @var \Drupal\config_perms\Entity\CustomPermsEntity $custom_perm */
    foreach ($custom_perms as $custom_perm) {
      if ($custom_perm->getStatus()) {
        $routes = config_perms_parse_path($custom_perm->getRoute());
        foreach ($routes as $route) {
          if ($route = $collection->get($route)) {
            // This overrides the route requirements removing all the other
            // access checkers and leaving only our access checker.
            $route->setRequirements(['_config_perms_access_check' => 'TRUE']);
          }
        }
      }
    }
  }

}
