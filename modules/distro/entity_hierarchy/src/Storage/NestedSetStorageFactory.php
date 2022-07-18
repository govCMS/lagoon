<?php

namespace Drupal\entity_hierarchy\Storage;

use Doctrine\DBAL\Connection;
use Drupal\Core\Database\Connection as DrupalConnection;
use Psr\Log\LoggerInterface;

/**
 * Defines a factory for creating a nested set storage handler for hierarchies.
 */
class NestedSetStorageFactory {

  /**
   * DBAL connection.
   *
   * @var \Doctrine\DBAL\Connection
   */
  protected $connection;

  /**
   * Static cache.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Table prefix.
   *
   * @var string
   */
  protected $tablePrefix = '';

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new NestedSetStorageFactory object.
   *
   * @param \Doctrine\DBAL\Connection $connection
   *   Dbal Connection.
   * @param \Drupal\Core\Database\Connection $drupalConnection
   *   Drupal connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(Connection $connection, DrupalConnection $drupalConnection, LoggerInterface $logger) {
    $this->connection = $connection;
    $this->tablePrefix = $drupalConnection->tablePrefix();
    $this->logger = $logger;
  }

  /**
   * Gets a new nested set storage handler.
   *
   * @param string $field_name
   *   Field name for storage.
   * @param string $entity_type_id
   *   Entity Type ID.
   *
   * @return \Drupal\entity_hierarchy\Storage\NestedSetStorage
   *   Nested set for given field.
   */
  public function get($field_name, $entity_type_id) {
    $table_name = $this->getTableName($field_name, $entity_type_id);
    return $this->fromTableName($table_name);
  }

  /**
   * Gets a new nested set storage handler for the given table.
   *
   * @param string $table_name
   *   Table name.
   *
   * @return \Drupal\entity_hierarchy\Storage\NestedSetStorage
   *   Nested set for given field.
   *
   * @todo Remove this in favour of derivative argument plugins?
   */
  public function fromTableName($table_name) {
    if (!isset($this->cache[$table_name])) {
      $this->cache[$table_name] = new NestedSetStorage($this->connection, $table_name, $this->logger);
    }
    return $this->cache[$table_name];
  }

  /**
   * Gets table name for a given field name and entity type.
   *
   * @param string $field_name
   *   Field name.
   * @param string $entity_type_id
   *   Entity Type ID.
   * @param bool $withPrefix
   *   (optional) TRUE to add prefix. Views data does not need prefix.
   *
   * @return string
   *   Table name.
   */
  public function getTableName($field_name, $entity_type_id, $withPrefix = TRUE) {
    return sprintf('%snested_set_%s_%s', $withPrefix ? $this->tablePrefix : '', $field_name, $entity_type_id);
  }

}
