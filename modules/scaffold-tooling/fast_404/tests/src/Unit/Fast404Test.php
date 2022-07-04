<?php

namespace Drupal\Tests\fast404\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests Fast404 methods.
 *
 * @coversDefaultClass \Drupal\fast404\Fast404
 * @group fast404
 */
class Fast404Test extends UnitTestCase {

  /**
   * Creates a fast404 object to test.
   *
   * @return \Drupal\fast404\Fast404
   *   A mock fast404 object to test.
   */
  protected function getFast404() {
    $requestStub = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();
    $fast404 = $this->getMockBuilder('\Drupal\fast404\Fast404')
      ->setConstructorArgs([$requestStub])
      ->setMethods(['isCli'])
      ->getMock();
    $fast404->method('isCli')
      ->willReturn(FALSE);
    return $fast404;
  }

  /**
   * Tests blocking a path.
   *
   * @covers ::blockPath
   */
  public function testBlockPath() {
    $fast404 = $this->getFast404();
    // Default value is FALSE for respond404.
    $this->assertEquals(FALSE, $fast404->isPathBlocked());
    $fast404->blockPath();
    // A block path's value is TRUE for respond404.
    $this->assertEquals(TRUE, $fast404->isPathBlocked());
  }

  /**
   * Tests checking if a path is blocked.
   *
   * @covers ::isPathBlocked
   */
  public function testIsPathBlocked() {
    $fast404 = $this->getFast404();
    $this->assertEquals(FALSE, $fast404->isPathBlocked());
    // If CLI, return FALSE regardless.
    $fast404->method('isCli')
      ->willReturn(TRUE);
    $this->assertEquals(FALSE, $fast404->isPathBlocked());
  }

  /**
   * Tests checking if a extension is blocked.
   *
   * @covers ::extensionCheck
   */
  // public function testExtensionCheck() {}

  /**
   * Tests checking if a path is blocked.
   *
   * @covers ::pathCheck
   */
  // public function testPathCheck() {}

  /**
   * Tests Fast404 responses.
   *
   * @covers ::response
   */
  // public function testResponse() {}

}
