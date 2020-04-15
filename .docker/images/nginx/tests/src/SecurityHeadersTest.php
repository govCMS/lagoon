<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Test frame options from the request.
 */
class SecurityHeadersTest extends TestCase {

  /**
   * Ensure that the X-XSS-Protection header is present.
   */
  public function testXssProtection() {
    $headers = \get_curl_headers("/");
    $this->assertEquals('1; mode=block', $headers['X-XSS-Protection']);
  }

  /**
   * Ensure taht the X-Content-Type-Options header is prsent.
   */
  public function testContentTypeOptions() {
    $headers = \get_curl_headers("/");
    $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
  }
}
