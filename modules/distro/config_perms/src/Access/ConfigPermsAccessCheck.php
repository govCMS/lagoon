<?php

namespace Drupal\config_perms\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for custom_perms routes.
 */
class ConfigPermsAccessCheck implements AccessInterface {

  /**
   * The entityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   The routeMatch object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Determine if the user is allowed to access the route.
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch) {
    $custom_perms_storage = $this->entityTypeManager->getStorage('custom_perms_entity');
    $params = [
      'status' => TRUE,
    ];
    $custom_perms = $custom_perms_storage->loadByProperties($params);
    $access_result = AccessResult::forbidden();

    // If for for some reason there is not a custom_perm then return forbidden.
    if (empty($custom_perms)) {
      return $access_result;
    }
    /** @var \Drupal\config_perms\Entity\CustomPermsEntity $custom_perm */
    foreach ($custom_perms as $custom_perm) {
      $routes = config_perms_parse_path($custom_perm->getRoute());
      if (!in_array($routeMatch->getRouteName(), $routes)) {
        continue;
      }

      $access_result = AccessResult::allowedIf($account->hasPermission($custom_perm->label()));
    }

    return $access_result;
  }

}
