<?php

namespace Drupal\google_analytics;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class JavascriptLocalCache {

  /**
   * Google Analytics Javascript URL.
   */
  const GOOGLE_ANALYTICS_JAVASCRIPT_URL =  'https://www.googletagmanager.com/gtag/js';

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  public function __construct(ClientInterface $http_client, FileSystemInterface $file_system, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, StateInterface $state, FileUrlGeneratorInterface $file_url_generator) {
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->logger = $logger_factory->get('google_analytics');
    $this->fileUrlGenerator = $file_url_generator;
  }
  /**
   * Download/Synchronize/Cache tracking code file locally.
   *
   * @param string $tracking_id
   *   The GA Tracking ID
   * @param bool $synchronize
   *   Synchronize to local cache if remote file has changed.
   *
   * @return string
   *   The path to the local or remote tracking file.
   */
  public function fetchGoogleAnalyticsJavascript(string $tracking_id, bool $synchronize = FALSE) {
    $path = 'public://google_analytics';
    $remote_url = self::GOOGLE_ANALYTICS_JAVASCRIPT_URL . '?id=' . $tracking_id;
    $file_destination = $path . '/gtag.js';

    // If cache is disabled, just return the URL for GA
    if (!$this->configFactory->get('google_analytics.settings')->get('cache')) {
      return $remote_url;
    }

    if (!file_exists($file_destination) || $synchronize) {
      // Download the latest tracking code.
      try {
        $data = (string) $this->httpClient
          ->get($remote_url)
          ->getBody();

        if (file_exists($file_destination)) {
          // Synchronize tracking code and replace local file if outdated.
          $data_hash_local = Crypt::hashBase64(file_get_contents($file_destination));
          $data_hash_remote = Crypt::hashBase64($data);
          // Check that the files directory is writable.
          if ($data_hash_local != $data_hash_remote && $this->fileSystem->prepareDirectory($path)) {
            // Save updated tracking code file to disk.
            $this->fileSystem->saveData($data, $file_destination, FileSystemInterface::EXISTS_REPLACE);
            // Based on Drupal Core class AssetDumper.
            if (extension_loaded('zlib') && $this->configFactory->get('system.performance')->get('js.gzip')) {
              $this->fileSystem->saveData(gzencode($data, 9, FORCE_GZIP), $file_destination . '.gz', FileSystemInterface::EXISTS_REPLACE);
            }
            $this->logger->info('Locally cached tracking code file has been updated.');

            // Change query-strings on css/js files to enforce reload for all
            // users.
            _drupal_flush_css_js();
          }
        }
        else {
          // Check that the files directory is writable.
          if ($this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY)) {
            // There is no need to flush JS here as core refreshes JS caches
            // automatically, if new files are added.
            $this->fileSystem->saveData($data, $file_destination, FileSystemInterface::EXISTS_REPLACE);
            // Based on Drupal Core class AssetDumper.
            if (extension_loaded('zlib') && $this->configFactory->get('system.performance')->get('js.gzip')) {
              $this->fileSystem->saveData(gzencode($data, 9, FORCE_GZIP), $file_destination . '.gz', FileSystemInterface::EXISTS_REPLACE);
            }
            $this->logger->info('Locally cached tracking code file has been saved.');
          }
        }
      }
      catch (RequestException $exception) {
        watchdog_exception('google_analytics', $exception);
        return $remote_url;
      }
    }
    // Return the local JS file path.
    $query_string = '?' . (\Drupal::state()->get('system.css_js_query_string') ?: '0');
    return $this->fileUrlGenerator->generateString($file_destination) . $query_string;
  }

  /**
   * Delete cached files and directory.
   */
  public function clearGoogleAnalyticsJsCache() {
    $path = 'public://google_analytics';
    if (is_dir($path)) {
      $this->fileSystem->deleteRecursive($path);

      // Change query-strings on css/js files to enforce reload for all users.
      _drupal_flush_css_js();

      $this->logger->info('Local Google Analytics file cache has been purged.');
    }
  }

}