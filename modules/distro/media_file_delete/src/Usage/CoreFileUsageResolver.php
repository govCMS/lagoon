<?php

declare(strict_types=1);

namespace Drupal\media_file_delete\Usage;

use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Defines a class for determining usage based on core's file usage.
 */
class CoreFileUsageResolver implements FileUsageResolverInterface {

  /**
   * File usage.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Constructs a new CoreFileUsage.
   *
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   File usage service from core.
   */
  public function __construct(FileUsageInterface $fileUsage) {
    $this->fileUsage = $fileUsage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUsages(FileInterface $file) : int {
    return array_reduce($this->fileUsage->listUsage($file), function (int $count, array $module_usage) {
      return $count + array_reduce($module_usage, function (int $object_count, array $object_usage) {
          return $object_count + count($object_usage);
      }, 0);
    }, 0);
  }

}
