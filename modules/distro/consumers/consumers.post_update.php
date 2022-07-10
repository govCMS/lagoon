<?php

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Makes the consumer entity type translatable.
 */
function consumers_post_update_make_consumer_entity_type_translatable(array &$sandbox) {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $entity_definition_update_manager->getEntityType('consumer');

  // If the entity type is already translatable, there's nothing we need to do.
  if ($entity_type->isTranslatable()) {
    return;
  }

  // Make the entity type translatable.
  $entity_type->set('translatable', TRUE);
  $entity_type->set('data_table', 'consumer_field_data');
  $keys = $entity_type->getKeys();
  $keys['langcode'] = 'langcode';
  $entity_type->set('entity_keys', $keys);

  // Create the new fields.
  $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('consumer');
  $field_storage_definitions['langcode'] = BaseFieldDefinition::create('language')
    ->setLabel(t('Language'))
    ->setDisplayOptions('view', [
      'region' => 'hidden',
    ])
    ->setDisplayOptions('form', [
      'type' => 'language_select',
      'weight' => 2,
    ])
    ->setTranslatable(TRUE)
    ->setName('langcode')
    ->setTargetEntityTypeId('consumer')
    ->setTargetBundle(NULL);

  $field_storage_definitions['default_langcode'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Default translation'))
    ->setDescription(t('A flag indicating whether this is the default translation.'))
    ->setTranslatable(TRUE)
    ->setRevisionable(TRUE)
    ->setDefaultValue(TRUE)
    ->setName('default_langcode')
    ->setTargetEntityTypeId('consumer')
    ->setTargetBundle(NULL);

  $entity_definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);

  return t('Consumers have been converted to be translatable.');
}
