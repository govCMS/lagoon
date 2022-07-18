<?php

namespace Drupal\entity_hierarchy\Information;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use PNX\NestedSet\Node;

/**
 * Defines a value object for a child entity warning.
 *
 * @see entity_hierarchy_form_alter()
 */
class ChildEntityWarning {

  /**
   * Related entities.
   *
   * @var \SplObjectStorage
   */
  protected $relatedEntities;

  /**
   * Cache metadata.
   *
   * @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   */
  protected $cache;

  /**
   * Node if parent exists.
   *
   * @var null|\PNX\NestedSet\Node
   */
  protected $parent;

  /**
   * Constructs a new ChildEntityWarning object.
   *
   * @param \SplObjectStorage $relatedEntities
   *   Related entities (children or parents).
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cache
   *   Cache metadata.
   * @param \PNX\NestedSet\Node|null $parent
   *   (optional) Parent if exists.
   */
  public function __construct(\SplObjectStorage $relatedEntities, RefinableCacheableDependencyInterface $cache, Node $parent = NULL) {
    $this->relatedEntities = $relatedEntities;
    $this->cache = $cache;
    $this->parent = $parent;
  }

  /**
   * Gets render array for child entity list.
   *
   * @return array
   *   Render array.
   */
  public function getList() {
    $child_labels = [];
    $build = ['#theme' => 'item_list'];
    foreach ($this->relatedEntities as $node) {
      if (!$this->relatedEntities->contains($node) || $node == $this->parent) {
        continue;
      }
      $child_labels[] = $this->relatedEntities->offsetGet($node)->label();
    }
    $build['#items'] = array_unique($child_labels);
    $this->cache->applyTo($build);
    return $build;
  }

  /**
   * Gets warning message for deleting a parent.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   Warning message.
   */
  public function getWarning() {
    if ($this->parent) {
      return new PluralTranslatableMarkup(
        // Related entities includes the parent, so we remove that.
        $this->relatedEntities->count() - 1,
        'This Test entity has 1 child, deleting this item will change its parent to be @parent.',
        'This Test entity has @count children, deleting this item will change their parent to be @parent.',
        [
          '@parent' => $this->relatedEntities->offsetGet($this->parent)->label(),
        ]);
    }
    return new PluralTranslatableMarkup(
      $this->relatedEntities->count(),
      'This Test entity has 1 child, deleting this item will move that item to the root of the hierarchy.',
      'This Test entity has @count children, deleting this item will move those items to the root of the hierarchy.');
  }

}
