<?php

namespace Drupal\lagoon_logs\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;

class LagoonLogsLoggerFactory {

  const LAGOON_LOGS_DEFAULT_SAFE_BRANCH = 'safe_branch_unset';

  const LAGOON_LOGS_DEFAULT_LAGOON_PROJECT = 'project_unset';


  public static function create(
    ConfigFactoryInterface $config,
    LogMessageParserInterface $parser
  ) {
    $host = $config->get('lagoon_logs.settings')->get('host');
    $port = $config->get('lagoon_logs.settings')->get('port');
    return new LagoonLogsLogger($host, $port, self::getHostProcessIndex($config), $parser);
  }

  public static function getHostProcessIndex(ConfigFactoryInterface $config) {
    $disabled = $config->get('lagoon_logs.settings')->get('disable');
    return $disabled != TRUE ?
      implode('-', [
        getenv('LAGOON_PROJECT') ?: self::LAGOON_LOGS_DEFAULT_LAGOON_PROJECT,
        getenv('LAGOON_GIT_SAFE_BRANCH') ?: self::LAGOON_LOGS_DEFAULT_SAFE_BRANCH,
      ]) :
      FALSE;
  }
}
