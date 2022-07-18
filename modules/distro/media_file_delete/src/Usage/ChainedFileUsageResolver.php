<?php

declare(strict_types=1);

namespace Drupal\media_file_delete\Usage;

use Drupal\file\FileInterface;

/**
 * Defines a chained resolver of file usage.
 */
class ChainedFileUsageResolver implements FileUsageResolverInterface {

  /**
   * Resolvers.
   *
   * @var \Drupal\media_file_delete\Usage\FileUsageResolverInterface
   */
  protected $usageResolvers = [];

  /**
   * Sorted resolvers.
   *
   * @var \Drupal\media_file_delete\Usage\FileUsageResolverInterface
   */
  protected $sortedResolvers = NULL;

  /**
   * Adds file usage resolver.
   *
   * @param \Drupal\media_file_delete\Usage\FileUsageResolverInterface $usage_resolver
   *   Resolver to add.
   * @param int $priority
   *   Service priority.
   */
  public function addFileUsageResolver(FileUsageResolverInterface $usage_resolver, int $priority) {
    $this->usageResolvers[$priority][] = $usage_resolver;
    $this->sortedResolvers = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUsages(FileInterface $file): int {
    $usage = 0;
    foreach ($this->getSortedResolvers() as $resolver) {
      assert($resolver instanceof FileUsageResolverInterface);
      $usage += $resolver->getFileUsages($file);
    }
    return $usage;
  }

  /**
   * Returns the sorted array of usage resolvers.
   *
   * @return \Drupal\media_file_delete\Usage\FileUsageResolverInterface[]
   *   An array of sorted array of usage resolvers.
   */
  protected function getSortedResolvers() {
    if (!isset($this->sortedResolvers)) {
      krsort($this->usageResolvers);
      $this->sortedResolvers = [];
      foreach ($this->usageResolvers as $resolvers) {
        $this->sortedResolvers = array_merge($this->sortedResolvers, $resolvers);
      }
    }
    return $this->sortedResolvers;
  }

}
