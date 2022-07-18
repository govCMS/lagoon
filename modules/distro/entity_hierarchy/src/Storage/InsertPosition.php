<?php

namespace Drupal\entity_hierarchy\Storage;

use PNX\NestedSet\Node;

/**
 * Defines a value object for an insert position.
 */
class InsertPosition {

  const ACTION_ADD_NODE = 'addNode';
  const DIRECTION_BEFORE = 'Before';
  const DIRECTION_AFTER = 'After';
  const DIRECTION_BELOW = 'Below';
  const ACTION_MOVE_SUB_TREE = 'moveSubTree';

  /**
   * Direction to insert, one of the DIRECTION_ constants.
   *
   * @var string
   */
  protected $direction;

  /**
   * Node to insert before or after.
   *
   * @var \PNX\NestedSet\Node
   */
  protected $reference;

  /**
   * TRUE to insert, FALSE to move.
   *
   * @var bool
   */
  protected $insert;

  /**
   * Constructs a new InsertPosition object.
   *
   * @param \PNX\NestedSet\Node $reference
   *   Node to insert before or after.
   * @param bool $insert
   *   TRUE if inserting rather than moving.
   * @param string $direction
   *   Direction constants - one of Before/After/Below.
   */
  public function __construct(Node $reference, $insert = TRUE, $direction = self::DIRECTION_BEFORE) {
    $this->reference = $reference;
    $this->direction = $direction;
    $this->insert = $insert;
  }

  /**
   * Perform the insert.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorage $storage
   *   Storage.
   * @param \PNX\NestedSet\Node|\PNX\NestedSet\NodeKey $node
   *   Node to create or insert.
   *
   * @return \PNX\NestedSet\Node
   *   Inserted node.
   */
  public function performInsert(NestedSetStorage $storage, $node) {
    $command = [
      'action' => self::ACTION_ADD_NODE,
      'direction' => $this->direction,
    ];
    if (!$this->insert) {
      $command['action'] = self::ACTION_MOVE_SUB_TREE;
    }
    $method = implode('', $command);
    return call_user_func_array([$storage, $method], [$this->reference, $node]);
  }

}
