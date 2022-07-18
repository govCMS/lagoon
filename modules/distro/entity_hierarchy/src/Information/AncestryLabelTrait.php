<?php

namespace Drupal\entity_hierarchy\Information;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a trait for ancestry labels.
 */
trait AncestryLabelTrait {

  /**
   * Tree node mapper.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $entityTreeNodeMapper;

  /**
   * Key factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $keyFactory;

  /**
   * Generate labels including ancestry.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to generate label for.
   * @param \PNX\NestedSet\NestedSetInterface|\Drupal\entity_hierarchy\Storage\NestedSetStorage $storage
   *   Tree storage.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param array $tags
   *   Cache tags.
   *
   * @return string
   *   Label with ancestry if applicable.
   */
  protected function generateEntityLabelWithAncestry(ContentEntityInterface $entity, $storage, $entity_type_id, &$tags = []) {
    $key = $this->keyFactory->fromEntity($entity);
    $ancestors = $storage->findAncestors($key);
    // Remove ourself.
    array_pop($ancestors);
    $ancestor_entities = $this->entityTreeNodeMapper->loadAndAccessCheckEntitysForTreeNodes($entity_type_id, $ancestors);
    $ancestors_labels = [];
    foreach ($ancestor_entities as $ancestor_node) {
      if (!$ancestor_entities->contains($ancestor_node)) {
        // Doesn't exist or is access hidden.
        continue;
      }
      $ancestor_entity = $ancestor_entities->offsetGet($ancestor_node);
      $ancestors_labels[] = $ancestor_entity->label();
      foreach ($ancestor_entity->getCacheTags() as $tag) {
        $tags[] = $tag;
      }
    }
    if (!$ancestors || !$ancestors_labels) {
      // No parents.
      return $entity->label();
    }
    return sprintf('%s (%s)', $entity->label(), implode(' ❭ ', $ancestors_labels));
  }

}
