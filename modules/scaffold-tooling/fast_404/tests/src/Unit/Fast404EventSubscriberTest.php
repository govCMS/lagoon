<?php

namespace Drupal\Tests\fast404\Unit;

use Drupal\fast404\EventSubscriber\Fast404EventSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the fast404 event subscriber logic.
 *
 * @coversDefaultClass \Drupal\fast404\EventSubscriber\Fast404EventSubscriber
 * @group fast404
 */
class Fast404EventSubscriberTest extends UnitTestCase {

  /**
   * The event.
   *
   * @var \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $kernel = $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = new Request();
    $this->event = new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, new NotFoundHttpException());
  }

  /**
   * Tests event handling for kernel requests.
   *
   * @covers ::onKernelRequest
   */
  // public function testOnKernelRequest() {}

  /**
   * Tests event handling for not found exceptions.
   *
   * @covers ::onNotFoundException
   */
  public function testOnNotFoundException() {
    $subscriber = $this->getFast404EventSubscriber();
    $subscriber->onNotFoundException($this->event);
    $e = $this->event->getException();
    $this->assertInstanceOf(NotFoundHttpException::class, $e);
  }

  /**
   * Creates a Fast404EventSubscriber object to test.
   *
   * @return \Drupal\fast404\EventSubscriber\Fast404EventSubscriber
   *   A mock Fast404EventSubscriber object to test.
   */
  protected function getFast404EventSubscriber() {
    $requestStackStub = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
      ->disableOriginalConstructor()
      ->getMock();
    $subscriber = $this->getMockBuilder('\Drupal\fast404\EventSubscriber\Fast404EventSubscriber')
      ->setConstructorArgs([$requestStackStub])
      ->getMock();
    return $subscriber;
  }

  /**
   * Tests event listener registration.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $this->assertEquals(
      [
        'kernel.request' => [['onKernelRequest', 100]],
        'kernel.exception' => [['onNotFoundException', 0]],
      ],
      Fast404EventSubscriber::getSubscribedEvents()
    );
  }

}
