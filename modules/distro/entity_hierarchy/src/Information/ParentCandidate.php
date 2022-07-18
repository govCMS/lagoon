<?php

namespace Drupal\entity_hierarchy\Information;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Defines a class for determining if an entity is a parent candidate.
 */
class ParentCandidate implements ParentCandidateInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new ReorderChildrenAccess object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle Info.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidateFields(EntityInterface $entity) {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference_hierarchy');
    $valid_fields = [];
    $entity_type = $entity->getEntityTypeId();
    if (isset($fields[$entity_type])) {
      // See if any bundles point to this entity.
      // We only consider this entity type, there is no point in a hierarchy
      // that spans entity-types as you cannot have more than a single level.
      foreach ($fields[$entity_type] as $field_name => $detail) {
        foreach ($detail['bundles'] as $bundle) {
          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
          $field = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle)[$field_name];
          $settings = $field->getSetting('handler_settings');
          if (!isset($settings['target_bundles']) || in_array($entity->bundle(), $settings['target_bundles'], TRUE)) {
            // No target bundles means any can be referenced, return early.
            $valid_fields[] = $field_name;
            continue 2;
          }
        }
      }
    }
    return $valid_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidateBundles(EntityInterface $entity) {
    $fields = $this->entityFieldManager->getFieldMap()[$entity->getEntityTypeId()];
    $bundles = [];
    $bundleInfo = $this->bundleInfo->getBundleInfo($entity->getEntityTypeId());
    foreach ($this->getCandidateFields($entity) as $field_name) {
      $valid_bundles = [];
      foreach ($fields[$field_name]['bundles'] as $bundle) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
        $field = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $bundle)[$field_name];
        $settings = $field->getSetting('handler_settings');
        if (!isset($settings['target_bundles']) || in_array($entity->bundle(), $settings['target_bundles'], TRUE)) {
          // No target bundles means any can be referenced.
          $valid_bundles[$bundle] = $bundle;
        }
      }
      $bundles[$field_name] = array_intersect_key($bundleInfo, $valid_bundles);
    }
    return $bundles;
  }

}
