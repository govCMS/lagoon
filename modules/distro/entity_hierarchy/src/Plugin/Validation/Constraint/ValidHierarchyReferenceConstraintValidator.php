<?php

namespace Drupal\entity_hierarchy\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use PNX\NestedSet\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidHierarchyReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Node storage factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * Node key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $keyFactory;

  /**
   * Constructs a ValidReferenceConstraintValidator object.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Nested set factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $keyFactory
   *   Key factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(NestedSetStorageFactory $nestedSetStorageFactory, NestedSetNodeKeyFactory $keyFactory, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->keyFactory = $keyFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_hierarchy.nested_set_storage_factory'),
      $container->get('entity_hierarchy.nested_set_node_factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var ValidHierarchyReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }

    // Collect new entities and IDs of existing entities across the field items.
    $new_entities = [];
    $target_ids = [];
    if (!$value->getEntity()) {
      // Can't validate if we don't have access to host entity.
      return;
    }
    foreach ($value as $delta => $item) {
      $target_id = $item->target_id;
      // We don't use a regular NotNull constraint for the target_id property as
      // NULL is allowed if the entity property contains an unsaved entity.
      // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints()
      if (!$item->isEmpty() && $target_id === NULL) {
        if (!$item->entity->isNew()) {
          $this->context->buildViolation($constraint->nullMessage)
            ->atPath((string) $delta)
            ->addViolation();
          return;
        }
        $new_entities[$delta] = $item->entity;
      }

      // '0' or NULL are considered valid empty references.
      if (!empty($target_id)) {
        $target_ids[$delta] = $target_id;
      }
    }

    // Early opt-out if nothing to validate.
    if (!$new_entities && !$target_ids) {
      return;
    }

    $this_entity = $value->getEntity();
    $thisNode = $this->keyFactory->fromEntity($this_entity);
    $target_type = $this_entity->getEntityTypeId();
    /** @var \PNX\NestedSet\Storage\DbalNestedSet $storage */
    $storage = $this->nestedSetStorageFactory->get($value->getFieldDefinition()->getFieldStorageDefinition()->getName(), $target_type);
    $children = array_map(function (Node $node) {
      return $node->getId();
    }, $storage->findDescendants($thisNode));
    // Cannot reference self either.
    $children[] = $this_entity->id();

    // Add violations on deltas with a target_id that is not valid.
    if ($target_ids && $children) {
      $deltas = array_flip($target_ids);
      foreach ($children as $entity_id) {
        if (isset($deltas[$entity_id])) {
          $this->context->buildViolation($constraint->message)
            ->setParameter('%type', $target_type)
            ->setParameter('%id', $entity_id)
            ->atPath((string) $deltas[$entity_id] . '.target_id')
            ->setInvalidValue($entity_id)
            ->addViolation();
        }
      }
    }
  }

}
