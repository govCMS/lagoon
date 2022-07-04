<?php

namespace Drupal\redis\Flood;

use Drupal\redis\ClientFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Flood backend singleton handling.
 */
class FloodFactory {

  /**
   * @var \Drupal\redis\ClientInterface
   */
  protected $clientFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the PhpRedis flood backend factory.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(ClientFactory $client_factory, RequestStack $request_stack) {
    $this->clientFactory = $client_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Get actual flood backend.
   *
   * @return \Drupal\Core\Flood\FloodInterface
   *   Return flood instance.
   */
  public function get() {
    $class_name = $this->clientFactory->getClass(ClientFactory::REDIS_IMPL_FLOOD);
    return new $class_name($this->clientFactory, $this->requestStack);
  }
}
