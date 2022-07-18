<?php

namespace Drupal\entity_hierarchy_microsite\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a class for a microsite cache context.
 */
class MicrositeCacheContext implements CacheContextInterface {

  const NOT_A_MICROSITE = -1;

  /**
   * Lookup.
   *
   * @var \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface
   */
  private $childOfMicrositeLookup;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Parent candidate.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  private $parentCandidate;

  /**
   * Constructs a new MicrositeCacheContext.
   *
   * @param \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface $childOfMicrositeLookup
   *   Lookup.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match.
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate.
   */
  public function __construct(ChildOfMicrositeLookupInterface $childOfMicrositeLookup, RouteMatchInterface $routeMatch, ParentCandidateInterface $parentCandidate) {
    $this->childOfMicrositeLookup = $childOfMicrositeLookup;
    $this->routeMatch = $routeMatch;
    $this->parentCandidate = $parentCandidate;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return 'Microsite ID';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $node = $this->routeMatch->getParameter('node');
    if (!$node || !($node instanceof NodeInterface)) {
      return self::NOT_A_MICROSITE;
    }
    if (!$fields = $this->parentCandidate->getCandidateFields($node)) {
      return self::NOT_A_MICROSITE;
    }
    foreach ($fields as $field_name) {
      if ($microsites = $this->childOfMicrositeLookup->findMicrositesForNodeAndField($node, $field_name)) {
        return implode(':', array_keys($microsites));
      }
    }
    return self::NOT_A_MICROSITE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
