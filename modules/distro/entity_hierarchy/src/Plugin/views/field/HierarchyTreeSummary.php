<?php

namespace Drupal\entity_hierarchy\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field that show hierarchy depth of item.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_hierarchy_tree_summary")
 */
class HierarchyTreeSummary extends FieldPluginBase {

  /**
   * Constructs a new HierarchyTreeSummary object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Definition.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Nested set storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nodeKeyFactory
   *   Node key factory.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $tree_mapper
   *   Nested set node to entity mapper.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NestedSetStorageFactory $nestedSetStorageFactory, EntityTypeManagerInterface $entityTypeManager, NestedSetNodeKeyFactory $nodeKeyFactory, EntityTreeNodeMapperInterface $tree_mapper, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeKeyFactory = $nodeKeyFactory;
    $this->nodeKeyFactory = $nodeKeyFactory;
    $this->treeMapper = $tree_mapper;
    $this->nestedSetPrefix = $database->tablePrefix();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_hierarchy.nested_set_storage_factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_hierarchy.nested_set_node_factory'),
      $container->get('entity_hierarchy.entity_tree_node_mapper'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['summary_type'] = [
      'default' => 'child_counts',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['summary_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'child_counts' => 'Number of children at each level below',
      ],
      '#title' => $this->t('Summary type'),
      '#default_value' => $this->options['summary_type'],
      '#description' => $this->t('Choose the summary type.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($this->options['summary_type'] == 'child_counts') {
      $storage = $this->getTreeStorage();
      $output = [];
      if ($entity = $this->getEntity($values)) {
        $stub = $this->nodeKeyFactory->fromEntity($entity);
        $level = 1;
        while ($entities = $storage->findDescendants($stub, 1, $level)) {
          // This is inefficient and one reason why this is only an admin tool.
          $entities = $this->treeMapper->loadEntitiesForTreeNodesWithoutAccessChecks($entity->getEntityTypeId(), $entities);
          $output[] = count($entities);
          $level++;
        }
      }
      return implode(' / ', $output);
    }
  }

  /**
   * Returns the tree storage.
   *
   * @return \Drupal\entity_hierarchy\Storage\NestedSetStorage
   *   Tree storage.
   */
  protected function getTreeStorage() {
    return $this->nestedSetStorageFactory->fromTableName($this->nestedSetPrefix . $this->table);
  }

}
