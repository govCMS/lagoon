<?php

namespace Drupal\config_update_ui\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LogMessageParserInterface;

/**
 * Provides Drush 8 logging in a class.
 */
class ConfigUpdateUiDrush8Logger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The message placeholder parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a ConfigUpdateUiDrush8Logger object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables. The service
   *   logger.log_message_parser is a good choice.
   */
  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Translate the RFC logging levels into their Drush counterparts, more or
    // less.
    // @todo ALERT, CRITICAL and EMERGENCY are considered show-stopping errors,
    // and they should cause Drush to exit or panic. Not sure how to handle
    // this, though.
    switch ($level) {
      case RfcLogLevel::ALERT:
      case RfcLogLevel::CRITICAL:
      case RfcLogLevel::EMERGENCY:
      case RfcLogLevel::ERROR:
        $error_type = LogLevel::ERROR;
        break;

      case RfcLogLevel::WARNING:
        $error_type = LogLevel::WARNING;
        break;

      case RfcLogLevel::DEBUG:
        $error_type = LogLevel::DEBUG;
        break;

      case RfcLogLevel::INFO:
        $error_type = LogLevel::INFO;
        break;

      case RfcLogLevel::NOTICE:
        $error_type = LogLevel::NOTICE;
        break;

      // TODO: Unknown log levels that are not defined
      // in Psr\Log\LogLevel or Drush\Log\LogLevel SHOULD NOT be used.  See
      // https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
      // We should convert these to 'notice'.
      default:
        $error_type = $level;
        break;
    }
    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    drush_log($message, $error_type);
  }

  /**
   * Implements a success() method to emulate \Consolidation\Log\Logger.
   *
   * @param string $message
   *   Translated message to print to STDERR.
   * @param array $context
   *   Ignored in this implementation.
   */
  public function success($message, array $context = []) {
    drush_print($message, 0, STDERR);
  }

}
