<?php

namespace Drupal\clamav\Scanner;

use Drupal\file\FileInterface;
use Drupal\clamav\ScannerInterface;
use Drupal\clamav\Scanner;
use Drupal\clamav\Config;

class DaemonUnixSocket implements ScannerInterface {
  protected $_file;
  protected $_unix_socket;
  protected $_virus_name = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config) {
    $this->_unix_socket = $config->get('mode_daemon_unixsocket.unixsocket');
  }

  /**
   * {@inheritdoc}
   */
  public function scan(FileInterface $file) {
    // Attempt to open a socket to the ClamAV host and the file.
    $file_handler    = fopen($file->getFileUri(), 'r');
    $scanner_handler = @fsockopen("unix://{$this->_unix_socket}", 0);

    // Abort if the ClamAV server is unavailable.
    if (!$scanner_handler) {
      \Drupal::logger('Clam AV')->warning('Unable to connect to ClamAV daemon on unix socket @unix_socket', array('@unix_socket' => $this->_unix_socket));
      return Scanner::FILE_IS_UNCHECKED;
    }

    // Push to the ClamAV socket.
    $bytes = $file->getSize();
    fwrite($scanner_handler, "zINSTREAM\0");
    fwrite($scanner_handler, pack("N", $bytes));
    stream_copy_to_stream($file_handler, $scanner_handler);

    // Send a zero-length block to indicate that we're done sending file data.
    fwrite($scanner_handler, pack("N", 0));

    // Request a response from the service.
    $response = trim(fgets($scanner_handler));

    fclose($scanner_handler);

    if (preg_match('/^stream: OK$/', $response)) {
      $result = Scanner::FILE_IS_CLEAN;
    }
    elseif (preg_match('/^stream: (.*) FOUND$/', $response, $matches)) {
      $this->_virus_name = $matches[1];
      $result = Scanner::FILE_IS_INFECTED;
    }
    else {
      preg_match('/^stream: (.*) ERROR$/', $response, $matches);
      $result = Scanner::FILE_IS_UNCHECKED;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function virus_name() {
    return $this->_virus_name;
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    $handler = @fsockopen("unix://{$this->_unix_socket}", 0);
    if (!$handler) {
      \Drupal::logger('Clam AV')->warning('Unable to connect to ClamAV daemon on unix socket @unix_socket', array('@unix_socket' => $this->_unix_socket));
      return NULL;
    }

    fwrite($handler, "VERSION\n");
    $content = fgets($handler);
    fclose($handler);
    return $content;
  }
}
