<?php

namespace Drupal\redis;

/**
 * Client proxy, client handling class tied to the bare minimum.
 */
interface ClientInterface {
  /**
   * Get the connected client instance.
   *
   * @return mixed
   *   Real client depends from the library behind.
   */
  public function getClient($host = NULL, $port = NULL, $base = NULL, $password = NULL, $replicationHosts = [], $persistent = FALSE);

  /**
   * Get underlying library name used.
   *
   * This can be useful for contribution code that may work with only some of
   * the provided clients.
   *
   * @return string
   */
  public function getName();
}
