<?php

declare(strict_types=1);

namespace Drupal\media_file_delete_entity_usage;

use Drupal\entity_usage\EntityUsageInterface;
use Drupal\file\FileInterface;
use Drupal\media_file_delete\Usage\FileUsageResolverInterface;

/**
 * Defines a usage resolver based off entity-usage module.
 */
class EntityUsageResolver implements FileUsageResolverInterface {

  /**
   * Entity usage.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * Constructs a new EntityUsageResolver.
   *
   * @param \Drupal\entity_usage\EntityUsageInterface $entityUsage
   *   Entity usage.
   */
  public function __construct(EntityUsageInterface $entityUsage) {
    $this->entityUsage = $entityUsage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUsages(FileInterface $file): int {
    return count($this->entityUsage->listSources($file, FALSE));
  }

}
