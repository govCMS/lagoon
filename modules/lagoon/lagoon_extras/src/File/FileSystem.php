<?php

namespace Drupal\lagoon_extras\File;

use Drupal\Core\File\FileSystem as BaseFileSystem;
use Drupal\lagoon_extras\Form\SettingsForm;

/**
 * Provide additional logging for filesystem syscalls.
 */
class FileSystem extends BaseFileSystem {

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    $status = parent::unlink($uri, $context);

    if ($status) {
      $this->logger->info("The file %uri was successfully unlinked", ['%uri' => $uri]);
      if (\Drupal::config(Form::SETTINGS)->get('verbose_logging')) {
        $this->logger->info('%backtrace', ['%backtrace' => json_encode(debug_backtrace())]);
      }
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($uri, $context = NULL) {
    $status = parent::rmdir($uri, $context);

    if ($status) {
      $this->logger->info('The directory %uri was successfully removed.', ['%uri' => $uri]);
      if (\Drupal::config(SettingsForm::SETTINGS)->get('verbose_logging')) {
        $this->logger->info('%backtrace', ['%backtrace' => json_encode(debug_backtrace())]);
      }
    }

    return $status;
  }

}
