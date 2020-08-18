<?php

namespace Drupal\clamav\Scanner;

use Drupal\file\FileInterface;
use Drupal\clamav\ScannerInterface;
use Drupal\clamav\Scanner;
use Drupal\clamav\Config;

class DaemonTCPIP implements ScannerInterface {
  protected $_file;
  protected $_hostname;
  protected $_port;
  protected $_virus_name = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config) {
    $this->_hostname = $config->get('mode_daemon_tcpip.hostname');
    $this->_port     = $config->get('mode_daemon_tcpip.port');
  }

  /**
   * {@inheritdoc}
   */
  public function scan(FileInterface $file) {

    // Attempt to open a socket to the ClamAV host.
    $scanner_handler = @fsockopen($this->_hostname, $this->_port);

    // Abort if the ClamAV server is unavailable.
    if (!$scanner_handler) {
      \Drupal::logger('Clam AV')->warning('Unable to connect to ClamAV TCP/IP daemon on @hostname:@port', array('@hostname' => $this->_hostname, '@port' => $this->_port));
      return Scanner::FILE_IS_UNCHECKED;
    }

    // Push to the ClamAV socket.
    $bytes = $file->getSize();
    fwrite($scanner_handler, "zINSTREAM\0");
    fwrite($scanner_handler, pack("N", $bytes));

    // Open the file and push to the TCP/IP connection.
    $file_handler = fopen($file->getFileUri(), 'r');
    stream_copy_to_stream($file_handler, $scanner_handler);

    // Send a zero-length block to indicate that we're done sending file data.
    fwrite($scanner_handler, pack("N", 0));

    // Request a response from the service.
    $response = trim(fgets($scanner_handler));

    // Close both handlers.
    fclose($scanner_handler);
    fclose($file_handler);

    // Process the output from the stream.
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
    $handler = @fsockopen($this->_hostname, $this->_port);
    if (!$handler) {
      \Drupal::logger('Clam AV')->warning('Unable to connect to ClamAV TCP/IP daemon on @hostname:@port', array('@hostname' => $this->_hostname, '@port' => $this->_port));
      return NULL;
    }

    fwrite($handler, "VERSION\n");
    $content = fgets($handler);
    fclose($handler);
    return $content;
  }
}
