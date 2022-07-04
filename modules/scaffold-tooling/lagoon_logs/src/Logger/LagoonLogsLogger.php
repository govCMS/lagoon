<?php

namespace Drupal\lagoon_logs\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\RfcLoggerTrait;
use Monolog\Formatter\JsonFormatter;
use Psr\Log\LoggerInterface;

use Monolog\Logger;
use Monolog\Handler\SocketHandler;

use Monolog\Formatter\LogstashFormatter;
use Drupal\lagoon_logs\LagoonLogsLogProcessor;


class LagoonLogsLogger implements LoggerInterface {

  use RfcLoggerTrait;

  const LAGOON_LOGS_MONOLOG_CHANNEL_NAME = 'LagoonLogs';

  const LAGOON_LOGS_DEFAULT_CHUNK_SIZE_BYTES = 15000; //will be used when new release of monolog is available

  const LAGOON_LOGS_DEFAULT_IDENTIFIER = 'drupal';

  //The following is used to log Lagoon Logs issues if logging target
  //cannot be reached.
  const LAGOON_LOGGER_WATCHDOG_FALLBACK_IDENTIFIER = 'lagoon_logs_fallback_error';

  // protected static $logger;

  protected $hostName;

  protected $hostPort;

  protected $logFullIdentifier;

  protected $parser;


  /**
   * See
   * https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#log-levels
   *
   * @var array
   */
  protected $rfcMonologErrorMap = [
    RfcLogLevel::EMERGENCY => 600,
    RfcLogLevel::ALERT => 550,
    RfcLogLevel::CRITICAL => 500,
    RfcLogLevel::ERROR => 400,
    RfcLogLevel::WARNING => 300,
    RfcLogLevel::NOTICE => 250,
    RfcLogLevel::INFO => 200,
    RfcLogLevel::DEBUG => 100,
  ];


  public function __construct(
    $host,
    $port,
    $logFullIdentifier,
    LogMessageParserInterface $parser
  ) {
    $this->hostName = $host;
    $this->hostPort = $port;
    $this->logFullIdentifier = $logFullIdentifier;
    $this->parser = $parser;
  }


  /**
   * @param $level
   * @param $message
   * @param array $context
   * @param $base_url
   *
   * @return array
   */
  protected function transformDataForProcessor(
    $level,
    $message,
    array $context,
    $base_url
  ) {
    $processorData = ["extra" => []];
    $processorData['message'] = $message;
    $processorData['extra']['ip'] = $context['ip'];
    $processorData['extra']['request_uri'] = $context['request_uri'];
    $processorData['level'] = $this->mapRFCtoMonologLevels($level);
    $processorData['extra']['uid'] = $context['uid'];
    $processorData['extra']['link'] = strip_tags($context['link']);
    $processorData['extra']['channel'] = $context['channel'];
    $processorData['extra']['application'] = self::LAGOON_LOGS_DEFAULT_IDENTIFIER;
    return $processorData;
  }


  protected function mapRFCtoMonologLevels(int $rfcErrorLevel) {
    return $this->rfcMonologErrorMap[$rfcErrorLevel];
  }

  protected function getRFCLevelName(int $rfcErrorLevel) {
    $levels = RfcLogLevel::getLevels();
    return $levels[$rfcErrorLevel];
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (!$this->logFullIdentifier) {
      return;
    }

    global $base_url;

    $logger = new Logger(
      !empty($context['channel']) ? $context['channel'] : self::LAGOON_LOGS_MONOLOG_CHANNEL_NAME
    );

    $connectionString = sprintf(
      "udp://%s:%s",
      $this->hostName,
      $this->hostPort
    );
    $udpHandler = new SocketHandler($connectionString);

    $udpHandler->setChunkSize(self::LAGOON_LOGS_DEFAULT_CHUNK_SIZE_BYTES);


    $udpHandler->setFormatter(
      new LagoonLogsFormatter($this->logFullIdentifier)
    );


    $logger->pushHandler($udpHandler);

    $message_placeholders = $this->parser->parseMessagePlaceholders(
      $message,
      $context
    );
    $message = strip_tags(
      empty($message_placeholders) ? $message : strtr(
        $message,
        $message_placeholders
      )
    );

    $processorData = $this->transformDataForProcessor(
      $level,
      $message,
      $context,
      $base_url
    );

    $logger->pushProcessor(new LagoonLogsLogProcessor($processorData));

    try {
      $logger->log($this->mapRFCtoMonologLevels($level), $message);
    } catch (\Exception $exception) {
    }
  }

}
