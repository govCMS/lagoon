<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An exceptional HTTP client mock.
 */
class MockHttpClient implements ClientInterface {

  /**
   * An exception message for the client methods.
   */
  const EXCEPTION_MESSAGE = "The HTTP mock can't do anything.";

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []): ResponseInterface {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $options = []): ResponseInterface {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync($method, $uri, array $options = []): PromiseInterface {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(?string $option = NULL) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * Patch up a magic method call.
   */
  public function head($url) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

}
