<?php

namespace Drupal\entity_hierarchy\Routing;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for limiting the children form to entities with hierarchies.
 */
class ReorderChildrenAccess implements AccessCheckInterface {

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Parent candidate service.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  protected $parentCandidate;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ReorderChildrenAccess object.
   *
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match.
   */
  public function __construct(ParentCandidateInterface $parentCandidate, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->parentCandidate = $parentCandidate;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement(EntityHierarchyRouteProvider::ENTITY_HIERARCHY_HAS_FIELD);
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   Route being access checked.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, Request $request = NULL, AccountInterface $account = NULL) {
    $entity_type = $route->getOption(EntityHierarchyRouteProvider::ENTITY_HIERARCHY_ENTITY_TYPE);
    $entity = $this->routeMatch->getParameter($entity_type);

    // If a user has configured a local task with views at e.g.
    // node/{node}/media then the {node} object isn't upcast.
    // @todo revisit this when
    //   https://www.drupal.org/project/drupal/issues/2528166 is resolved
    if (!$entity instanceof EntityInterface) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $storage->load($entity);
      if (!$entity) {
        // At this point we can't proceed so we err on the side of caution and
        // deny access to the tab.
        return AccessResult::forbidden();
      }
    }

    if ($entity && $this->parentCandidate->getCandidateFields($entity)) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    return AccessResult::forbidden();
  }

}
