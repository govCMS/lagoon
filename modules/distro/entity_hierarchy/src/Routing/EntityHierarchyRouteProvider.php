<?php

namespace Drupal\entity_hierarchy\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a class for providing route definitions for hierarchy entities.
 */
class EntityHierarchyRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {
  const ENTITY_HIERARCHY_HAS_FIELD = '_entity_hierarchy_has_field';
  const ENTITY_HIERARCHY_ENTITY_TYPE = '_entity_hierarchy_entity_type';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new DefaultHtmlRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Provides routes for entities.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\RouteCollection|\Symfony\Component\Routing\Route[]
   *   Returns a route collection or an array of routes keyed by name, like
   *   route_callbacks inside 'routing.yml' files.
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    if ($entity_type->hasLinkTemplate('entity_hierarchy_reorder')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('latest-version'));
      $route
        ->setPath($entity_type->getLinkTemplate('canonical') . '/children')
        ->addDefaults([
          '_title' => 'Reorder children',
          '_entity_form' => "$entity_type_id.entity_hierarchy_reorder",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setRequirement('_permission', 'reorder entity_hierarchy children')
        ->setRequirement(self::ENTITY_HIERARCHY_HAS_FIELD, 'TRUE')
        ->setOption(self::ENTITY_HIERARCHY_ENTITY_TYPE, $entity_type_id)
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          $entity_type_id => [
            'type' => 'entity:' . $entity_type_id,
            'load_latest_revision' => FALSE,
          ],
        ]);
      $collection->add("entity.$entity_type_id.entity_hierarchy_reorder", $route);
    }
    return $collection;
  }

}
