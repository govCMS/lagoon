<?php

namespace Drupal\redis\Flood;

use Drupal\Core\Flood\FloodInterface;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the database flood backend. This is the default Drupal backend.
 */
class Predis implements FloodInterface {

  use RedisPrefixTrait;

  /**
   * @var \Predis\Client
   */
  protected $client;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the PhpRedis flood backend.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(ClientFactory $client_factory, RequestStack $request_stack) {
    $this->client = $client_factory->getClient();
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    $key = $this->getPrefix() . ':flood:' . $name . ':' . $identifier;

    // Add a key for the event to the sorted set, the score is timestamp, so we
    // can count them easily.
    $this->client->zAdd($key, $_SERVER['REQUEST_TIME'] + $window, microtime(TRUE));
    // Set or update the expiration for the sorted set, it will be removed if
    // the newest entry expired.
    $this->client->expire($key, $_SERVER['REQUEST_TIME'] + $window);
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    $key = $this->getPrefix() . ':flood:' . $name . ':' . $identifier;
    $this->client->del($key);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    $key = $this->getPrefix() . ':flood:' . $name . ':' . $identifier;

    // Count the in the last $window seconds.
    $number = $this->client->zCount($key, $_SERVER['REQUEST_TIME'], 'inf');
    return ($number < $threshold);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // No garbage collection necessary.
  }

}
