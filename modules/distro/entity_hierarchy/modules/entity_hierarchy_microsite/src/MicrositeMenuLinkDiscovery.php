<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\Form\MenuLinkDefaultForm;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface;
use Drupal\entity_hierarchy_microsite\Form\MicrositeMenuItemForm;
use Drupal\entity_hierarchy_microsite\Plugin\Menu\MicrositeMenuItem;

/**
 * Defines a class for microsite menu link discovery.
 */
class MicrositeMenuLinkDiscovery implements MicrositeMenuLinkDiscoveryInterface {
  /**
   * Set storage.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * Mapper.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $mapper;

  /**
   * Parent candidate.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  protected $candidate;

  /**
   * Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  private $keyFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new MicrositeMenuItemDeriver.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Storage.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $mapper
   *   Mapper.
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $candidate
   *   Candidate.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Type manager.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $keyFactory
   *   Key factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(NestedSetStorageFactory $nestedSetStorageFactory, EntityTreeNodeMapperInterface $mapper, ParentCandidateInterface $candidate, EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, NestedSetNodeKeyFactory $keyFactory, ModuleHandlerInterface $moduleHandler) {
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->mapper = $mapper;
    $this->candidate = $candidate;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->keyFactory = $keyFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuLinkDefinitions(MicrositeInterface $microsite = NULL) {
    $definitions = [];
    $microsites = $microsite ? [$microsite] : $this->entityTypeManager->getStorage('entity_hierarchy_microsite')->loadMultiple();
    /** @var \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface $microsite */
    foreach ($microsites as $microsite) {
      $home = $microsite->getHome();
      if (!$home) {
        continue;
      }
      $key = $this->keyFactory->fromEntity($home);
      $parentUuids = [];
      foreach ($this->candidate->getCandidateFields($home) as $field_name) {
        /** @var \PNX\NestedSet\NestedSetInterface $tree */
        $tree = $this->nestedSetStorageFactory->get($field_name, 'node');
        $homeNode = $tree->getNode($key);
        if (!$homeNode) {
          // No children.
          continue;
        }
        $nodes = $this->mapper->loadEntitiesForTreeNodesWithoutAccessChecks('node', $tree->findDescendants($key));
        $url = $home->toUrl();
        $definitions[$home->uuid()] = [
          'class' => MicrositeMenuItem::class,
          'menu_name' => 'entity-hierarchy-microsite',
          'route_name' => $url->getRouteName(),
          'route_parameters' => $url->getRouteParameters(),
          'options' => $url->getOptions(),
          'title' => $home->label(),
          'description' => '',
          'weight' => $homeNode->getLeft(),
          'id' => 'entity_hierarchy_microsite:' . $home->uuid(),
          'metadata' => ['entity_id' => $home->id(), 'entity_hierarchy_depth' => $homeNode->getDepth()],
          'form_class' => MenuLinkDefaultForm::class,
          'enabled' => 1,
          'expanded' => 1,
          'provider' => 'entity_hierarchy_microsite',
          'discovered' => 0,
        ];
        /** @var \PNX\NestedSet\Node $treeNode */
        foreach ($nodes as $treeNode) {
          if (!$nodes->contains($treeNode)) {
            continue;
          }
          /** @var \Drupal\node\NodeInterface $item */
          $item = $nodes->offsetGet($treeNode);
          $url = $item->toUrl();
          $revisionKey = sprintf('%s:%s', $treeNode->getId(), $treeNode->getRevisionId());
          $itemUuid = $item->uuid();
          $parentUuids[$revisionKey] = $itemUuid;
          $definitions[$itemUuid] = [
            'class' => MicrositeMenuItem::class,
            'menu_name' => 'entity-hierarchy-microsite',
            'route_name' => $url->getRouteName(),
            'route_parameters' => $url->getRouteParameters(),
            'options' => $url->getOptions(),
            'title' => $item->label(),
            'description' => '',
            'weight' => $treeNode->getLeft(),
            'id' => 'entity_hierarchy_microsite:' . $itemUuid,
            'metadata' => ['entity_id' => $item->id(), 'entity_hierarchy_depth' => $treeNode->getDepth()],
            'form_class' => MenuLinkDefaultForm::class,
            'enabled' => 1,
            'expanded' => 1,
            'provider' => 'entity_hierarchy_microsite',
            'discovered' => 0,
            'parent' => 'entity_hierarchy_microsite:' . $home->uuid(),
          ];
          $parent = $tree->findParent($treeNode->getNodeKey());
          if ($parent && ($parentRevisionKey = sprintf('%s:%s', $parent->getId(), $parent->getRevisionId())) && array_key_exists($parentRevisionKey, $parentUuids)) {
            $definitions[$itemUuid]['parent'] = 'entity_hierarchy_microsite:' . $parentUuids[$parentRevisionKey];
          }
        }
      }
      /** @var \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface $override */
      if ($definitions) {
        foreach ($this->entityTypeManager->getStorage('eh_microsite_menu_override')
          ->loadByProperties([
            'target' => array_keys($definitions),
          ]) as $override) {
          $original = $definitions[$override->getTarget()];
          $definitions[$override->getTarget()] = [
            'metadata' => [
              'original' => array_intersect_key($original, [
                'title' => TRUE,
                'weight' => TRUE,
                'enabled' => TRUE,
                'expanded' => TRUE,
                'parent' => TRUE,
              ]),
            ] + $original['metadata'],
            'title' => $override->label(),
            'form_class' => MicrositeMenuItemForm::class,
            'weight' => $override->getWeight(),
            'enabled' => $override->isEnabled(),
            'expanded' => $override->isExpanded(),
            'parent' => $override->getParent(),
          ] + $original;
        }
      }
    }

    $this->moduleHandler->alter('entity_hierarchy_microsite_links', $definitions);

    return $definitions;
  }

}
