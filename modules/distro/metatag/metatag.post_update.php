<?php

/**
 * @file
 * Post update functions for Metatag.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\metatag\Entity\MetatagDefaults;

/**
 * Convert mask-icon to array values.
 */
function metatag_post_update_convert_mask_icon_to_array_values(&$sandbox) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $metatag_defaults) {
    if ($metatag_defaults->hasTag('mask-icon')) {
      $tags = $metatag_defaults->get('tags');
      $tags['mask_icon'] = [
        'href' => $metatag_defaults->getTag('mask-icon'),
      ];
      unset($tags['mask-icon']);
      $metatag_defaults->set('tags', $tags);
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * The author meta tag was moved into the main module: configuration.
 */
function metatag_post_update_convert_author_config(&$sandbox) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $metatag_defaults) {
    if ($metatag_defaults->hasTag('google_plus_author')) {
      $tags = $metatag_defaults->get('tags');
      $tags['author'] = $metatag_defaults->getTag('google_plus_author');
      unset($tags['google_plus_author']);
      $metatag_defaults->set('tags', $tags);
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * The author meta tag was moved into the main module: entity data.
 */
function metatag_post_update_convert_author_data(&$sandbox) {
  // This whole top section only needs to be done the first time.
  if (!isset($sandbox['records_processed'])) {
    $sandbox['records_processed'] = 0;
    $sandbox['total_records'] = 0;
    $sandbox['current_field'] = 0;
    $sandbox['current_record'] = 0;

    // Counter to enumerate the fields so we can access them in the array
    // by number rather than name.
    $field_counter = 0;

    // Get all of the field storage entities of type metatag.
    /** @var \Drupal\field\FieldStorageConfigInterface[] $field_storage_configs */
    $field_storage_configs = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->loadByProperties(['type' => 'metatag']);

    foreach ($field_storage_configs as $field_storage) {
      $field_name = $field_storage->getName();

      // Get the individual fields (field instances) associated with bundles.
      $fields = \Drupal::entityTypeManager()
        ->getStorage('field_config')
        ->loadByProperties([
          'field_name' => $field_name,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
        ]);

      foreach ($fields as $field) {
        // Get the bundle this field is attached to.
        $bundle = $field->getTargetBundle();

        // Determine the table and "value" field names.
        $table_mapping = Drupal::entityTypeManager()
          ->getStorage($field->getTargetEntityTypeId())
          ->getTableMapping();
        $field_table = $table_mapping->getFieldTableName($field_name);
        $field_value_field = $table_mapping->getFieldColumnName($field_storage, 'value');

        // Get all records where the field data does not match the default.
        $query = \Drupal::database()->select($field_table);
        $query->addField($field_table, 'entity_id');
        $query->addField($field_table, 'revision_id');
        $query->addField($field_table, 'langcode');
        $query->addField($field_table, $field_value_field);
        $query->condition('bundle', $bundle, '=');
        $result = $query->execute();
        $records = $result->fetchAll();

        if (empty($records)) {
          continue;
        }

        // Fill in all the sandbox information so we can batch the individual
        // record comparing and updating.
        $sandbox['fields'][$field_counter]['field_table'] = $field_table;
        $sandbox['fields'][$field_counter]['field_value_field'] = $field_value_field;
        $sandbox['fields'][$field_counter]['records'] = $records;

        $sandbox['total_records'] += count($sandbox['fields'][$field_counter]['records'] = $records);
        $field_counter++;
      }
    }
  }

  if ($sandbox['total_records'] == 0) {
    // No partially overridden fields so we can skip the whole batch process.
    $sandbox['#finished'] = 1;
  }
  else {
    // Begin the batch processing of individual field records.
    $max_per_batch = 10;
    $counter = 1;

    $current_field = $sandbox['current_field'];
    $current_field_records = $sandbox['fields'][$current_field]['records'];
    $current_record = $sandbox['current_record'];

    $field_table = $sandbox['fields'][$current_field]['field_table'];
    $field_value_field = $sandbox['fields'][$current_field]['field_value_field'];

    // Loop through the field(s) and update the mask_icon values if necessary.
    while ($counter <= $max_per_batch && isset($current_field_records[$current_record])) {
      $record = $current_field_records[$current_record];

      // Strip any empty tags or ones matching the field's defaults and leave
      // only the overridden tags in $new_tags.
      $tags = unserialize($record->$field_value_field);
      if (isset($tags['google_plus_author'])) {
        $tags['author'] = $tags['google_plus_author'];
        $tags_string = serialize($tags);
        \Drupal::database()->update($field_table)
          ->fields([
            $field_value_field => $tags_string,
          ])
          ->condition('entity_id', $record->entity_id)
          ->condition('revision_id', $record->revision_id)
          ->condition('langcode', $record->langcode)
          ->execute();
      }
      $counter++;
      $current_record++;
    }

    // We ran out of records for the field so start the next batch out with the
    // next field.
    if (!isset($current_field_records[$current_record])) {
      $current_field++;
      $current_record = 0;
    }

    // We have finished all the fields. All done.
    if (!isset($sandbox['fields'][$current_field])) {
      $sandbox['records_processed'] += $counter - 1;
      $sandbox['#finished'] = 1;
    }
    // Update the sandbox values to prepare for the next round.
    else {
      $sandbox['current_field'] = $current_field;
      $sandbox['current_record'] = $current_record;
      $sandbox['records_processed'] += $counter - 1;
      $sandbox['#finished'] = $sandbox['records_processed'] / $sandbox['total_records'];
    }
  }

  if ($sandbox['total_records'] > 0) {
    return (string) t('Processed @processed of @total overridden Metatag records.', [
      '@processed' => $sandbox['records_processed'],
      '@total' => $sandbox['total_records'],
    ]);
  }
  else {
    return (string) t("There were no overridden Metatag records.");
  }
}
