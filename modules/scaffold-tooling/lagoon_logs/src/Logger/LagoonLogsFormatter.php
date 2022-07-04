<?php


namespace Drupal\lagoon_logs\Logger;

use Monolog\Formatter\LogstashFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;


/**
 * Class LagoonLogsFormatter
 *
 * For the first version of LL to support Monolog 2 we need to capture and
 * mangle the output from the JSON formatter
 *
 * @package Drupal\lagoon_logs\Logger
 */
class LagoonLogsFormatter extends LogstashFormatter {

  public function __construct($applicationName) {
    if (Logger::API == 1) {
      parent::__construct($applicationName, NULL, NULL, 'ctxt_', 1);
    }
    else {
      parent::__construct($applicationName, NULL, 'extra', 'ctxt_');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function format(array $record): string {
    $record = NormalizerFormatter::format($record);

    if (empty($record['datetime'])) {
      $record['datetime'] = gmdate('c');
    }
    $message = [
      '@timestamp' => $record['datetime'],
      '@version' => 1,
      'host' => $this->systemName,
    ];
    if (isset($record['message'])) {
      $message['message'] = $record['message'];
    }
    if (isset($record['channel'])) {
      $message['type'] = $record['channel'];
      $message['channel'] = $record['channel'];
    }
    if (isset($record['level_name'])) {
      $message['level'] = $record['level_name'];
    }
    if (isset($record['level'])) {
      $message['monolog_level'] = $record['level'];
    }
    if ($this->applicationName) {
      $message['type'] = $this->applicationName;
    }

    if (!empty($record['extra'])) {
        foreach ($record['extra'] as $key => $val) {
        $message[$key] = $val;
      }
    }

    if (!empty($record['context'])) {
      foreach ($record['context'] as $key => $val) {
        $message[$key] = $val;
      }
    }

    return $this->toJson($message) . "\n";
  }

}
