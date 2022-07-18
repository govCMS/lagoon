<?php

declare(strict_types=1);

namespace Drupal\media_file_delete\Usage;

use Drupal\file\FileInterface;

/**
 * Defines an interface for checking if a file has usage.
 */
interface FileUsageResolverInterface {

  /**
   * Gets file usage.
   *
   * @param \Drupal\file\FileInterface $file
   *   File.
   *
   * @return int
   *   Count of usage.
   */
  public function getFileUsages(FileInterface $file) : int;

}
