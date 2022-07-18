<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines a class for storage of overrides.
 */
class MicrositeMenuItemOverrideStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == $this->storage->getBaseTable() && $field_name === 'target') {
      // Target should have a unique index. This is a) for performance and b)
      // to prevent the presence of two overrides for the one derivative.
      $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
    }

    return $schema;
  }

}
