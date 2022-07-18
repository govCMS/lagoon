<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Component\Utility\Number;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a class for rebuilding the tree.
 */
class TreeRebuilder {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new TreeRebuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets rebuild tasks suitable for usage with batch_set().
   *
   * @param string $field_name
   *   Field name to rebuild.
   * @param string $entity_type_id
   *   Entity Type to rebuild.
   *
   * @return array
   *   Batch definition.
   */
  public function getRebuildTasks($field_name, $entity_type_id) {
    $batch = [
      'title' => new TranslatableMarkup('Rebuilding tree for field @field, @entity_type_id ...', [
        '@field' => $field_name,
        '@entity_type_id' => $entity_type_id,
      ]),
      'operations' => [
        [[static::class, 'removeTable'], [$field_name, $entity_type_id]],
      ],
      'finished' => [static::class, 'batchFinished'],
    ];
    $entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $idKey = $entityType->getKey('id');
    $query = $this->entityTypeManager->getStorage($entity_type_id)
      ->getAggregateQuery()
      ->groupBy("$field_name.target_id")
      ->groupBy("$field_name.weight")
      ->groupBy($idKey)
      ->sort("$field_name.target_id")
      ->sort("$field_name.weight")
      ->exists($field_name)
      ->accessCheck(FALSE);
    $records = $query->execute();
    $sorted = $this->treeSort($field_name, $records, $idKey, $entity_type_id);
    foreach ($sorted as $entity_id => $entry) {
      $batch['operations'][] = [
        [static::class, 'rebuildTree'],
        [$field_name, $entity_type_id, $entity_id],
      ];
    }
    return $batch;
  }

  /**
   * Batch callback to remove table.
   *
   * @param string $field_name
   *   Field name.
   * @param string $entity_type_id
   *   Entity Type ID.
   */
  public static function removeTable($field_name, $entity_type_id) {
    \Drupal::database()
      ->schema()
      ->dropTable(\Drupal::service('entity_hierarchy.nested_set_storage_factory')
        ->getTableName($field_name, $entity_type_id, FALSE));
  }

  /**
   * Sort callback.
   *
   * @param array $a
   *   Item.
   * @param array $b
   *   Item.
   *
   * @return int
   *   Sort order.
   */
  protected function sortItems(array $a, array $b) {
    $a_path = (string) $a['materialized_path'];
    $b_path = (string) $b['materialized_path'];
    if ($a_path === $b_path) {
      return 0;
    }
    return ($a_path < $b_path) ? -1 : 1;
  }

  /**
   * Batch callback to rebuild the tree.
   *
   * @param string $field_name
   *   Field name.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param mixed $entity_id
   *   Entity ID.
   * @param array $context
   *   Batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  //@codingStandardsIgnoreStart
  public static function rebuildTree($field_name, $entity_type_id, $entity_id, &$context) {
    //@codingStandardsIgnoreEnd
    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);
    $entity->get($field_name)->postSave(TRUE);
    self::debug(sprintf('Rebuilt %s', $entity_id));
    $context['results'][] = $entity_id;
  }

  /**
   * Finished callback.
   *
   * @param bool $success
   *   TRUE if succeeded.
   * @param int $results
   *   Results.
   * @param array $operations
   *   Operations.
   */
  //@codingStandardsIgnoreStart
  public static function batchFinished($success, $results, $operations) {
    //@codingStandardsIgnoreEnd
    if ($success) {
      // Here we do something meaningful with the results.
      $message = new TranslatableMarkup('Finished rebuilding tree, @count items were processed.', [
        '@count' => count($results),
      ]);
      \Drupal::messenger()->addMessage($message);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = new TranslatableMarkup('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => implode('::', $error_operation[0]),
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      \Drupal::messenger()->addMessage($message, 'error');
    }
  }

  /**
   * Sorts tree.
   *
   * @param string $field_name
   *   Field name of parent field.
   * @param array $records
   *   Records to sort.
   * @param string $idKey
   *   Field name of ID.
   * @param string $entity_type_id
   *   Entity type id.
   *
   * @return array
   *   Sorted records.
   */
  protected function treeSort($field_name, array $records, $idKey, string $entity_type_id) {
    $items = [];
    $weights = [];
    $sets = [];
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $weight_separator = isset($base_field_definitions[$field_name]) ? '__' : '_';
    foreach ($records as $ix => $item) {
      $parent = $item["$field_name{$weight_separator}target_id"];
      $sets[$parent][] = $item[$idKey];
      $items[$item[$idKey]] = $parent;
    }
    // Add in root items.
    foreach (array_keys($sets) as $parent) {
      if (!isset($items[$parent])) {
        $items[$parent] = 0;
        $sets[0][] = $parent;
      }
    }
    $flipped_sets = array_map(function (array $items) {
      return array_flip($items);
    }, $sets);
    foreach ($items as $id => $parent) {
      $flipped = $flipped_sets[$parent];
      if (isset($weights[$id])) {
        // We've already done this one via a child.
        continue;
      }
      $weights[$id] = [$flipped[$id]];
      if (!isset($weights[$parent]) && isset($items[$parent])) {
        $this->buildThread($weights, $items, $parent, $items[$parent], $flipped_sets);
      }
      if (isset($weights[$parent])) {
        $weights[$id] = array_merge($weights[$parent], $weights[$id]);
      }
    }
    $sorted = array_map(function (array $item) {
      return [
        'materialized_path' => implode('.', array_map([
          Number::class,
          'intToAlphadecimal',
        ], $item)),
      ];
    }, $weights);

    // Sort.
    uasort($sorted, [$this, 'sortItems']);

    // Remove root items.
    return array_diff_key($sorted, array_flip($sets[0]));
  }

  /**
   * Build thread for a given item ID and parent.
   *
   * @param array $weights
   *   Existing thread weights.
   * @param array $items
   *   All items.
   * @param int $id
   *   Item ID.
   * @param int $parent
   *   Parent ID.
   * @param array $flipped_sets
   *   Items grouped by parent ID, ordered by weight.
   */
  protected function buildThread(array &$weights, array $items, $id, $parent, array $flipped_sets) {
    $flipped = $flipped_sets[$parent];
    $weights[$id] = [$flipped[$id]];
    if (isset($items[$parent])) {
      $next_parent = $items[$parent];
      $flipped = $flipped_sets[$next_parent];
      $weights[$parent] = [$flipped[$parent]];
      if (!isset($weights[$next_parent]) && isset($items[$next_parent])) {
        $this->buildThread($weights, $items, $next_parent, $items[$next_parent], $flipped_sets);
      }
      if (isset($weights[$next_parent])) {
        $weights[$parent] = array_merge($weights[$next_parent], $weights[$parent]);
      }
      $weights[$id] = array_merge($weights[$parent], $weights[$id]);
    }
  }

  /**
   * Outputs a debug message.
   *
   * @param string $message
   *   Message to output.
   */
  protected static function debug($message) {
    \Drupal::logger($message);
  }

}
