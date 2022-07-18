<?php

namespace Drupal\entity_hierarchy\Information;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for building a list of child entity warnings.
 */
class ChildEntityWarningBuilder implements ContainerInjectionInterface {

  /**
   * Parent candidate info.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  protected $parentCandidate;

  /**
   * Tree node mapper.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $treeNodeMapper;

  /**
   * Storage factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * Node key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $nodeKeyFactory;

  /**
   * Constructs a new ChildEntityWarningBuilder object.
   *
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate service.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $treeNodeMapper
   *   Tree node mapper.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Storage factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nodeKeyFactory
   *   Key factory.
   */
  public function __construct(ParentCandidateInterface $parentCandidate, EntityTreeNodeMapperInterface $treeNodeMapper, NestedSetStorageFactory $nestedSetStorageFactory, NestedSetNodeKeyFactory $nodeKeyFactory) {
    $this->parentCandidate = $parentCandidate;
    $this->treeNodeMapper = $treeNodeMapper;
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->nodeKeyFactory = $nodeKeyFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_hierarchy.information.parent_candidate'),
      $container->get('entity_hierarchy.entity_tree_node_mapper'),
      $container->get('entity_hierarchy.nested_set_storage_factory'),
      $container->get('entity_hierarchy.nested_set_node_factory')
    );
  }

  /**
   * Gets warning about child entities before deleting a parent.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent
   *   Parent to be deleted.
   *
   * @return \Drupal\entity_hierarchy\Information\ChildEntityWarning[]
   *   Array of warning value objects.
   */
  public function buildChildEntityWarnings(ContentEntityInterface $parent) {
    $return = [];
    if ($fields = $this->parentCandidate->getCandidateFields($parent)) {
      $cache = new CacheableMetadata();
      foreach ($fields as $field_name) {
        /** @var \PNX\NestedSet\NestedSetInterface $storage */
        $storage = $this->nestedSetStorageFactory->get($field_name, $parent->getEntityTypeId());
        $nodeKey = $this->nodeKeyFactory->fromEntity($parent);
        $children = $storage->findChildren($nodeKey);
        if ($parent_node = $storage->findParent($nodeKey)) {
          $children[] = $parent_node;
        }
        $entities = $this->treeNodeMapper->loadAndAccessCheckEntitysForTreeNodes($parent->getEntityTypeId(), $children, $cache);
        $return[] = new ChildEntityWarning($entities, $cache, $parent_node);
      }
    }
    return $return;
  }

}
