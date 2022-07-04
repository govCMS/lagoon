<?php

namespace Drupal\lagoon_logs;

// use Monolog\Processor\Abstract;

class LagoonLogsLogProcessor {

  protected $processData;

  public function __construct(array $processData) {
    $this->processData = $processData;
  }

  /**
   * @param  array $record
   *
   * @return array
   */
  public function __invoke(array $record) {
    foreach ($this->processData as $key => $value) {
      if (empty($record[$key])) {
        $record[$key] = $value;
      }
    }
    return $record;
  }
}
