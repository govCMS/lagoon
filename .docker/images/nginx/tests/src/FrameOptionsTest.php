<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Test frame options from the request.
 */
class FrameOptionsTest extends TestCase
{

  /**
   * Ensure that the X-Frame-Option header is present.
   */
  public function testHeaderExists()
  {
    $headers = \get_curl_headers("/");
    $this->assertEquals('SameOrigin', $headers['X-Frame-Options']);
  }
}
