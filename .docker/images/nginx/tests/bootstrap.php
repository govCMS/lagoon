<?php

/**
 * PHPUnit Bootstrap file.
 */

include_once __DIR__ . '/vendor/autoload.php';

function get_curl_headers($path = "/", $opts = NULL)
{
  $uri = getenv('LOCALDEV_URL') ?: 'http://nginx:8080';

  $response = null;

  $path = '/' . ltrim($path, '/');

  exec("docker-compose exec -T test curl {$uri}{$path} -I {$opts} 2>/dev/null", $response);

  if (empty($response)) {
    return [];
  }

  $response = array_map('trim', $response);

  foreach ($response as $line) {
    if (strpos($line, 'HTTP') !== false) {
      $part = explode(' ', $line);
      $headers['Status'] = trim($part[1]);
      continue;
    }
    $part = explode(':', $line);
    if (count($part) == 2) {
      $headers[$part[0]] = trim($part[1]);
    }
  }

  return $headers;
}

function curl_get_content($path = "/", $opts = NULL)
{
  $uri = getenv('LOCALDEV_URL') ?: 'http://nginx:8080';

  $response = null;
  $path = '/' . ltrim($path, '/');

  exec("docker-compose exec -T test curl {$uri}{$path} {$opts} 2>/dev/null", $response);

  if (empty($response)) {
    return FALSE;
  }

  return $response;
}
