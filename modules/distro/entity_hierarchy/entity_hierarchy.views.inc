<?php

/**
 * @file
 * Views hooks for entity_hierarchy_views.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data().
 */
function entity_hierarchy_views_data() {
  // Adds the relationship from the entity-base table to any tree tables that
  // exist by way of entity reference hierarchy fields.
  $data = [];
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
  $entityTypeManager = \Drupal::service('entity_type.manager');
  /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
  $fieldManager = \Drupal::service('entity_field.manager');
  foreach ($fieldManager->getFieldMapByFieldType('entity_reference_hierarchy') as $entity_type_id => $fields) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $entityTypeManager->getDefinition($entity_type_id);
    if (!$entityTypeManager->hasHandler($entity_type_id, 'views_data')) {
      // If there is no existing views integration, we cannot do anything here.
      continue;
    }
    /** @var \Drupal\views\EntityViewsDataInterface $views_handler */
    $views_handler = $entityTypeManager->getHandler($entity_type_id, 'views_data');
    foreach ($fields as $field_name => $field_info) {
      $table_name = \Drupal::service('entity_hierarchy.nested_set_storage_factory')
        ->getTableName($field_name, $entity_type_id, FALSE);
      $sample_bundle = reset($field_info['bundles']);
      /** @var Drupal\Core\Field\FieldDefinitionInterface $sample_field */
      $sample_field = $fieldManager->getFieldDefinitions($entity_type_id, $sample_bundle)[$field_name];
      $data[$table_name]['table']['group'] = t('Entity hierarchy: @entity_type - @field_name', [
        '@entity_type' => $entity_type->getLabel(),
        '@field_name' => $sample_field->getLabel(),
      ]);
      $base_table = $views_handler->getViewsTableForEntityType($entity_type);

      // Relationship to the entity base table.
      $data[$table_name]['table']['join'] = [
        $base_table => [
          'left_field' => $entity_type->getKey('id'),
          'field' => 'id',
        ],
      ];
      if ($has_revisions = $entity_type->hasKey('revision')) {
        $revision_key = $entity_type->getKey('revision');
        $data[$table_name]['table']['join'][$base_table]['left_field'] = $revision_key;
        $data[$table_name]['table']['join'][$base_table]['field'] = 'revision_id';
      }

      // @see \PNX\NestedSet\Storage\DbalNestedSetSchema::create()
      // Sort for left position in tree.
      $data[$table_name]['left_pos'] = [
        'title' => t('Hierarchy order'),
        'help' => t('Sort in hierarchy order'),
        'sort' => [
          'id' => 'standard',
        ],
      ];
      // Contextual filter for filtering to children of a given parent.
      $data[$table_name]['is_child'] = [
        'title' => t('Hierarchy: Is child of'),
        'help' => t('Limit to children of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_child_of_entity_revision' : 'entity_hierarchy_argument_is_child_of_entity',
        ],
      ];
      // Contextual filter for filtering to parent of a given child.
      $data[$table_name]['is_parent'] = [
        'title' => t('Hierarchy: Is Parent of'),
        'help' => t('Limit to parent of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_parent_of_entity_revision' : 'entity_hierarchy_argument_is_parent_of_entity',
        ],
      ];
      // Contextual filter for filtering to sibling of a given child.
      $data[$table_name]['is_sibling'] = [
        'title' => t('Hierarchy: Is Sibling of'),
        'help' => t('Limit to sibling of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_sibling_of_entity_revision' : 'entity_hierarchy_argument_is_sibling_of_entity',
        ],
      ];
      // Sorting and filtering on depth.
      $data[$table_name]['depth'] = [
        'title' => t('Hierarchy depth'),
        'help' => t('Depth in hierarchy'),
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'argument' => [
          'id' => 'standard',
        ],
        'field' => [
          'id' => 'numeric',
        ],
      ];
      // Summarises the children for admin usage.
      $data[$table_name]['tree_summary'] = [
        'title' => t('Child summary'),
        'help' => t('Administrative field to show information about the children.'),
        'field' => [
          'id' => 'entity_hierarchy_tree_summary',
        ],
      ];
      // Additional relationship from base table to field.
      $data[$base_table]['tree'] = [
        'title' => t('Entity hierarchy'),
        'help' => t('The hierarchy information'),
        'real field' => $has_revisions ? $revision_key : $entity_type->getKey('id'),
        'group' => t('Entity hierarchy'),
        'relationship' => [
          'title' => 'Entity Hierarchy',
          'help' => 'Relate to hierarchy information',
          'id' => 'standard',
          'base' => $table_name,
          'base field' => $has_revisions ? 'revision_id' : 'id',
          'field' => $has_revisions ? $revision_key : $entity_type->getKey('id'),
        ],
      ];
    }
  }

  return $data;
}

/**
 * Implements hook_field_views_data().
 */
function entity_hierarchy_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $entityTypeManager = \Drupal::entityTypeManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $table_mapping = $entityTypeManager->getStorage($entity_type_id)->getTableMapping();
  $field_name = $field_storage->getName();

  if (!$entityTypeManager->hasHandler($entity_type_id, 'views_data')) {
    return [];
  }

  // Identify all the target entity type ids that can be referenced.
  foreach ($data as $table_name => $table_data) {
    // Add a relationship to all the target entity types.
    $entity_type = $entityTypeManager->getDefinition($entity_type_id);
    $target_base_table = $entityTypeManager->getHandler($entity_type_id, 'views_data')
      ->getViewsTableForEntityType($entity_type);

    $args = [
      '@label' => $entity_type->getLabel(),
      '@field_name' => $field_name,
    ];

    // Normal entity reference to parent.
    $data[$table_name]["{$entity_type_id}__{$field_name}"]['relationship'] = [
      'title' => t('Parent via @field_name', $args),
      'label' => t('Parent', $args),
      'group' => t('Entity hierarchy: @label - @field_name', $args),
      'help' => t('The parent of this content via @field_name.', $args),
      'id' => 'standard',
      'base' => $target_base_table,
      'base field' => $entity_type->getKey('id'),
      'entity type' => $entity_type_id,
      'relationship field' => $field_name . '_target_id',
    ];

    // Reverse entity reference to children.
    $data[$target_base_table]["reverse__{$entity_type_id}__{$field_name}"]['relationship'] = [
      'title' => t('Children via @field_name', $args),
      'label' => t('Children'),
      'group' => t('Entity hierarchy: @label - @field_name', $args),
      'help' => t('Children that refer to this content via @field_name.', $args),
      'id' => 'entity_reverse',
      'base' => $entityTypeManager->getHandler($entity_type_id, 'views_data')
        ->getViewsTableForEntityType($entity_type),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_id',
    ];

    // Relationship to root.
    $data[$target_base_table]["top__{$entity_type_id}__{$field_name}"]['relationship'] = [
      'title' => t('Root of hierarchy via @field_name', $args),
      'label' => t('Top', $args),
      'group' => t('Entity hierarchy: @label - @field_name', $args),
      'help' => t('The root of this hierarchy via @field_name.', $args),
      'id' => 'entity_hierarchy_root',
      'table' => $target_base_table,
      'base' => $target_base_table,
      'left_field' => $entity_type->getKey('id'),
      'nested_set_table' => "nested_set_{$field_name}_{$entity_type_id}",
    ];

    if ($revision_key = $entity_type->getKey('revision')) {
      $data[$target_base_table]["top__{$entity_type_id}__{$field_name}"]['relationship']['extra'] = [
        'revision' => [
          'left_field' => $revision_key,
        ],
      ];
    }
  }

  return $data;
}
