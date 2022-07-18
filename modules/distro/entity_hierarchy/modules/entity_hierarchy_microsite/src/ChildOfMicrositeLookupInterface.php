<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\node\NodeInterface;

/**
 * Defines a class for looking up a microsite given a node.
 */
interface ChildOfMicrositeLookupInterface {

  /**
   * Gets microsites the node belongs to.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   * @param string $field_name
   *   Field name.
   *
   * @return \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface[]
   *   Microsites.
   */
  public function findMicrositesForNodeAndField(NodeInterface $node, $field_name);

}
