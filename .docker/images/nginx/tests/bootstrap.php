<?php

/**
 * PHPUnit Bootstrap file.
 */

include_once __DIR__ . '/vendor/autoload.php';

/**
 * Execute CURL and get response headers.
 *
 * @param string $path
 *   (optional) URL path to get the headers for. Defaults to '/'.
 * @param null|string $opts
 *   (optional) CURL-compatible string of headers. Defaults to NULL.
 *
 * @return array
 *   Array of returned headers.
 *
 * @throws \RuntimeException
 *   If CURL exited with an error.
 */
function get_curl_headers($path = '/', $opts = NULL) {
  $uri = 'http://nginx:8080';
  $uri = $uri . '/' . ltrim($path, '/');

  $response = NULL;
  exec("docker-compose exec -T test curl -s {$uri} -I {$opts} 2>&1", $response, $ret);

  if (is_debug()) {
    fwrite(STDERR, sprintf('CURL exit code : %s', $ret) . PHP_EOL);
    fwrite(STDERR, sprintf('CURL URI       : %s', $uri) . PHP_EOL);
    fwrite(STDERR, sprintf('CURL response  : %s', PHP_EOL . implode(PHP_EOL, $response)) . PHP_EOL);
  }

  if ($ret != 0) {
    throw new \RuntimeException(sprintf('CURL exited with error code "%s" and response %s.', $ret, PHP_EOL . implode(PHP_EOL, $response)));
  }

  $response = array_map('trim', $response);

  foreach ($response as $line) {
    if (strpos($line, 'HTTP') !== FALSE) {
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

/**
 * Execute CURL and get response.
 *
 * @param string $path
 *   (optional) URL path to get the headers for. Defaults to '/'.
 * @param null|string $opts
 *   (optional) String of CURL options. Defaults to NULL.
 *
 * @return string
 *   Response as a string.
 *
 * @throws \RuntimeException
 *   If CURL exited with an error.
 */
function curl_get_content($path = '/', $opts = NULL) {
  $uri = 'http://nginx:8080';
  $uri = $uri . '/' . ltrim($path, '/');

  $response = NULL;
  exec("docker-compose exec -T test curl -s {$uri} {$opts} 2>&1", $response, $ret);

  if (is_debug()) {
    fwrite(STDERR, sprintf('CURL exit code : %s', $ret) . PHP_EOL);
    fwrite(STDERR, sprintf('CURL URI       : %s', $uri) . PHP_EOL);
    fwrite(STDERR, sprintf('CURL response  : %s', PHP_EOL . implode(PHP_EOL, $response)) . PHP_EOL);
  }

  if ($ret != 0) {
    throw new \RuntimeException(sprintf('CURL exited with error code "%s" and response %s.', $ret, PHP_EOL . implode(PHP_EOL, $response)));
  }

  return $response;
}

/**
 * Check if tests are running in debug mode.
 */
function is_debug() {
  return in_array('--debug', $_SERVER['argv'], TRUE);
}
