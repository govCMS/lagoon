<?php

namespace Drupal\entity_hierarchy\Plugin\views\relationship;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Relationship handler to return the top of the hierarchy.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_hierarchy_root")
 */
class HierarchyRoot extends RelationshipPluginBase {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new HierarchyIsParentOfEntity object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Create a sub-select which finds depth 0 parents of any child.
    $ns_table = $this->definition['nested_set_table'];
    $query = $this->database->select($ns_table, 'eh_child');
    $query->addField('eh_child', 'id', 'eh_child_id');
    $query->addField('eh_child', 'revision_id', 'eh_child_revision_id');
    $query->addField('eh_parent', 'id', $this->definition['left_field']);
    $query->addJoin(
      'INNER',
      $ns_table,
      'eh_parent',
      '(eh_child.left_pos BETWEEN eh_parent.left_pos AND eh_parent.right_pos) AND eh_parent.depth = 0',
    );

    $def = $this->definition;
    $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
    $def['table formula'] = $query;
    $def['left_table'] = $this->tableAlias;
    $def['field'] = 'eh_child_id';
    $def['adjusted'] = TRUE;

    // Define revision field to join to if needed.
    if (isset($def['extra']['revision'])) {
      $def['extra']['revision']['field'] = 'eh_child_revision_id';
    }

    $id = !empty($def['join_id']) ? $def['join_id'] : 'standard';
    $join = Views::pluginManager('join')->createInstance($id, $def);
    $alias = 'eh_for_' . $this->table;
    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);
  }

}
