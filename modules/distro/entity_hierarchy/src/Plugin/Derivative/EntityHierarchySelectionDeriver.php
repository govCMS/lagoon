<?php

namespace Drupal\entity_hierarchy\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives an entity reference selection handler for each entity type.
 */
class EntityHierarchySelectionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates an EntityHierarchySelectionDeriverobject.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityFieldManager->getFieldMapByFieldType('entity_reference_hierarchy') as $entity_type_id => $info) {
      foreach ($info as $field_name => $bundles) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $sample_field */
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, reset($bundles['bundles']));
        $sample_field_name = $field_name;
        if (isset($fields[$field_name])) {
          $sample_field_name = $fields[$field_name]->getName();
        }
        $key = $entity_type_id;
        $this->derivatives[$key] = $base_plugin_definition;
        $this->derivatives[$key]['entity_types'] = [$entity_type_id];
        $this->derivatives[$key]['field_name'] = $field_name;
        $this->derivatives[$key]['label'] = t('Selection with hierarchy (@field_name)', ['@field_name' => $sample_field_name]);
        $this->derivatives[$key]['base_plugin_label'] = (string) $base_plugin_definition['label'];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
