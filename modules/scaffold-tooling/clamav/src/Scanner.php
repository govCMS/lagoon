<?php

namespace Drupal\clamav;

use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\FileInterface;
use Drupal\clamav\Config;

/**
 * Service class for the ClamAV scanner instance.
 *
 * Passes the methods "scan" and "version" to a specific handler, according to
 * the configuration.
 */
class Scanner {

  // Constants defining the infection state of a specific file.
  const FILE_IS_UNCHECKED = -1;
  const FILE_IS_CLEAN     = 0;
  const FILE_IS_INFECTED  = 1;

  // Constants defining whether a specific file should be scanned.
  const FILE_IS_SCANNABLE     = TRUE;
  const FILE_IS_NOT_SCANNABLE = FALSE;
  const FILE_SCANNABLE_IGNORE = NULL;


  // Instance of a scanner class, implementing ScannerInterface.
  protected $scanner = NULL;

  // ClamAV configuration.
  protected $config = NULL;

  /**
   * Constructor.
   *
   * @param object $config
   *   An instance of \Drupal\clamav\Config.
   */
  public function __construct(\Drupal\clamav\Config $config) {
    $this->config = $config;

    switch ($config->scan_mode()) {
      case Config::MODE_EXECUTABLE:
        $this->scanner = new Scanner\Executable($this->config);
        break;

      case Config::MODE_DAEMON:
        $this->scanner = new Scanner\DaemonTCPIP($this->config);
        break;

      case Config::MODE_UNIX_SOCKET:
        $this->scanner = new Scanner\DaemonUnixSocket($this->config);
        break;
    }
  }

  /**
   * Check whether the anti-virus checks are enabled.
   *
   * @return boolean
   *   TRUE if files should be scanned.
   */
  public function isEnabled() {
    return $this->config->enabled();
  }

  /**
   * Check whether files that have not been scanned can be uploaded.
   *
   * @return boolean
   *    TRUE if unchecked files are permitted.
   */
  public function allowUncheckedFiles() {
    return $this->config->outage_action() == Config::OUTAGE_ALLOW_UNCHECKED;
  }

  /**
   * Check whether files that have not been scanned can be uploaded.
   *
   * @return boolean
   *    TRUE if unchecked files are permitted.
   */
  public function isVerboseModeEnabled() {
    return $this->config->verbosity();
  }


  /**
   * Check whether a specific file should be scanned by ClamAV.
   *
   * Specific files can be excluded from anti-virus scanning, such as:
   * - Image files
   * - Large files that might take a long time to scan
   * - Files uploaded by trusted administrators
   * - Viruses, intended to be deliberately uploaded to a virus database
   *
   * Files can be excluded from the scans by implementing
   * hook_clamav_file_is_scannable().
   *
   * @see hook_clamav_file_is_scannable().
   *
   * @return boolean
   *    TRUE if a file should be scanned by the anti-virus service.
   */
  public function isScannable(FileInterface $file) {
    // Check whether this stream-wrapper scheme is scannable.
      if (!empty($file->destination)) {
        $scheme = \Drupal::service('stream_wrapper_manager')->getScheme($file->destination);
      }
      else {
        $scheme = \Drupal::service('stream_wrapper_manager')->getScheme($file->getFileUri());
      }
    $scannable = self::isSchemeScannable($scheme);

    // Iterate each module implementing hook_clamav_file_is_scannable().
    // Modules that do not wish to affact the result should return
    // FILE_SCANNABLE_IGNORE.
    foreach (\Drupal::moduleHandler()->getImplementations('clamav_file_is_scannable') as $module) {
      $result = \Drupal::moduleHandler()->invoke($module, 'clamav_file_is_scannable', array($file));
      if ($result !== self::FILE_SCANNABLE_IGNORE) {
        $scannable = $result;
      }
    }

    return $scannable;
  }


  /**
   * Scan a file for viruses.
   *
   * @param Drupal\file\FileInterface $file
   *   The file to scan for viruses.
   *
   * @return int
   *   One of the following class constants:
   *   - CLAMAV_SCANRESULT_UNCHECKED
   *     The file was not scanned. The ClamAV service may be unavailable.
   *   - CLAMAV_SCANRESULT_CLEAN
   *     The file was scanned, and no infection was found.
   *   - CLAMAV_SCANRESULT_INFECTED
   *     The file was scanned, and found to be infected with a virus.
   */
  public function scan(FileInterface $file) {
    // Empty files are never infected.
    if ($file->getSize() === 0) {
      return self::FILE_IS_CLEAN;
    }

    $result = $this->scanner->scan($file);

    // Prepare to log results.
    $verbose_mode = $this->config->verbosity();
    $replacements = array(
      '%filename'  => $file->getFileUri(),
      '%virusname' => $this->scanner->virus_name(),
    );

    switch ($result) {
      // Log every infected file.
      case self::FILE_IS_INFECTED:
        $message = 'Virus %virusname detected in uploaded file %filename.';
        \Drupal::logger('Clam AV')->error($message, $replacements);
        break;

      // Log clean files if verbose mode is enabled.
      case self::FILE_IS_CLEAN:
        if ($verbose_mode) {
          $message = 'Uploaded file %filename checked and found clean.';
          \Drupal::logger('Clam AV')->info($message, $replacements);
        }
        break;

      // Log unchecked files if they are accepted, or verbose mode is enabled.
      case self::FILE_IS_UNCHECKED:
        if ($this->config->outage_action() == Config::OUTAGE_ALLOW_UNCHECKED) {
          $message = 'Uploaded file %filename could not be checked, and was uploaded without checking.';
          \Drupal::logger('Clam AV')->notice($message, $replacements);
        }
        elseif ($verbose_mode) {
          $message = 'Uploaded file %filename could not be checked, and was deleted.';
          \Drupal::logger('Clam AV')->info($message, $replacements);
        }
        break;
    }
    return $result;
  }

  /**
   * The version of the ClamAV service.
   *
   * @return string
   *   The version number provided by ClamAV.
   */
  public function version() {
    return $this->scanner->version();
  }

  /**
   * Determine whether files of a given scheme should be scanned.
   *
   * @param string $scheme
   *   The machine name of a stream-wrapper scheme, such as "public", or
   *   "youtube".
   *
   * @return boolean
   *   TRUE if the scheme should be scanned.
   */
  public static function isSchemeScannable($scheme) {
    if (empty($scheme)) {
      return TRUE;
    }

    // By default all local schemes should be scannable.
    $mgr = \Drupal::service('stream_wrapper_manager');
    $local_schemes = array_keys($mgr->getWrappers(StreamWrapperInterface::LOCAL));
    $scheme_is_local = in_array($scheme, $local_schemes);

    // The default can be overridden per scheme.
    $config = \Drupal::config('clamav.settings');
    $overridden_schemes = $config->get('overridden_schemes');
    $scheme_is_overridden = in_array($scheme, $overridden_schemes);

    return ($scheme_is_local xor $scheme_is_overridden);
  }
}
