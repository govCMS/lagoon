<?php

namespace Drupal\clamav;

use Drupal\file\FileInterface;
use Drupal\clamav\Config;

/**
 * Provides an interface defining a menu entity.
 */
interface ScannerInterface {

  /**
   * Constructor.
   *
   * @param Drupal\clamav\Config $config
   *   Configuration to use.
   */
  public function __construct(Config $config);

  /**
   * Scan a file.
   *
   * @param Drupal\file\FileInterface $file
   *   The file to scan for viruses.
   *
   * @return int
   *   - Scanner::FILE_IS_CLEAN
   *   - Scanner::FILE_IS_INFECTED
   *   - Scanner::FILE_IS_UNCHECKED
   */
  public function scan(FileInterface $file);

  /**
   * The version of the ClamAV service.
   *
   * @return string
   *   The version number provided by ClamAV.
   */
  public function version();
}
