<?php

namespace Drupal\entity_hierarchy_breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Symfony\Component\Routing\Route;

/**
 * Entity hierarchy based breadcrumb builder.
 */
class HierarchyBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The nested set storage factory service.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $storageFactory;

  /**
   * The node key factory service.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $nodeKeyFactory;

  /**
   * The entity tree node mapper service.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $mapper;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * HierarchyBasedBreadcrumbBuilder constructor.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $storage_factory
   *   The nested set storage factory service.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $node_key_factory
   *   The node key factory service.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $mapper
   *   The entity tree node mapper service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   */
  public function __construct(
    NestedSetStorageFactory $storage_factory,
    NestedSetNodeKeyFactory $node_key_factory,
    EntityTreeNodeMapperInterface $mapper,
    EntityFieldManagerInterface $entity_field_manager,
    AdminContext $admin_context
  ) {
    $this->storageFactory = $storage_factory;
    $this->nodeKeyFactory = $node_key_factory;
    $this->mapper = $mapper;
    $this->entityFieldManager = $entity_field_manager;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($this->adminContext->isAdminRoute($route_match->getRouteObject())) {
      return FALSE;
    }
    $route_entity = $this->getEntityFromRouteMatch($route_match);
    if (!$route_entity || !$route_entity instanceof ContentEntityInterface || !$this->getHierarchyFieldFromEntity($route_entity)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $route_entity */
    $route_entity = $this->getEntityFromRouteMatch($route_match);

    $entity_type = $route_entity->getEntityTypeId();
    $storage = $this->storageFactory->get($this->getHierarchyFieldFromEntity($route_entity), $entity_type);
    $ancestors = $storage->findAncestors($this->nodeKeyFactory->fromEntity($route_entity));
    // Pass in the breadcrumb object for caching.
    $ancestor_entities = $this->mapper->loadAndAccessCheckEntitysForTreeNodes($entity_type, $ancestors, $breadcrumb);

    $links = [];
    foreach ($ancestor_entities as $ancestor_entity) {
      if (!$ancestor_entities->contains($ancestor_entity)) {
        // Doesn't exist or is access hidden.
        continue;
      }
      $entity = $ancestor_entities->offsetGet($ancestor_entity);
      // Show just the label for the entity from the route.
      if ($entity->id() == $route_entity->id()) {
        $links[] = Link::createFromRoute($entity->label(), '<none>');
      }
      else {
        $links[] = $entity->toLink();
      }
    }

    array_unshift($links, Link::createFromRoute(new TranslatableMarkup('Home'), '<front>'));
    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }

  /**
   * Return the entity type id from a route object.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return string|null
   *   The entity type id, null if it doesn't exist.
   */
  protected function getEntityTypeFromRoute(Route $route) {
    if (!empty($route->getOptions()['parameters'])) {
      foreach ($route->getOptions()['parameters'] as $option) {
        if (isset($option['type']) && strpos($option['type'], 'entity:') === 0) {
          return substr($option['type'], strlen('entity:'));
        }
      }
    }

    return NULL;
  }

  /**
   * Returns an entity parameter from a route match object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or null if it's not an entity route.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$route) {
      return NULL;
    }

    $entity_type_id = $this->getEntityTypeFromRoute($route);
    if ($entity_type_id) {
      return $route_match->getParameter($entity_type_id);
    }

    return NULL;
  }

  /**
   * Gets the field name to use for the hierarchy.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   The field name, or null if there are no fields to use.
   */
  protected function getHierarchyFieldFromEntity(ContentEntityInterface $entity) {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference_hierarchy');
    $entity_type = $entity->getEntityTypeId();
    if (isset($fields[$entity_type])) {
      foreach ($fields[$entity_type] as $field_name => $detail) {
        if (!empty($detail['bundles'][$entity->bundle()])) {
          return $field_name;
        }
      }
    }

    return NULL;
  }

}
