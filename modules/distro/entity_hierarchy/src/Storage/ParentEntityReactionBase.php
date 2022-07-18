<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for reacting to parent entity updates.
 */
abstract class ParentEntityReactionBase implements ContainerInjectionInterface {

  use TreeLockTrait;

  /**
   * Nested set storage.
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
   * Parent candidate interface.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  protected $parentCandidate;

  /**
   * Constructs a new ParentEntityRevisionUpdater object.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Nested set storage factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nodeKeyFactory
   *   Node key factory.
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate service.
   */
  public function __construct(NestedSetStorageFactory $nestedSetStorageFactory, NestedSetNodeKeyFactory $nodeKeyFactory, ParentCandidateInterface $parentCandidate) {
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->nodeKeyFactory = $nodeKeyFactory;
    $this->parentCandidate = $parentCandidate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static(
      $container->get('entity_hierarchy.nested_set_storage_factory'),
      $container->get('entity_hierarchy.nested_set_node_factory'),
      $container->get('entity_hierarchy.information.parent_candidate')
    ))->setLockBackend($container->get('lock'));
  }

}
