<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that bots are not allowed.
 */
class RobotsTagTest extends TestCase
{

  /**
   * Provide a list of blocked host names.
   *
   * @return array
   *   A list of invalid host names.
   */
  public function provideInvalidHosts()
  {
    return [
      ['test.govcms.gov.au'],
      ['wsa.govcms.gov.au'],
      ['multi.subdomain.govcms.gov.au'],
      ['www2.govcms.gov.au'],
      ['test.govcms.gov.au'],
    ];
  }

  /**
   * Return valid domains.
   *
   * @return array
   *   An array of valid domains.
   */
  public function provideValidHosts()
  {
    return [
      ['test.gov.au'],
      ['betahealth-sr.gov.au'],
      ['www.govcms.gov.au'],
    ];
  }

  /**
   * Ensure that the X-Robots-Tag is set to none.
   *
   * @dataProvider provideInvalidHosts
   */
  public function testXRobotsNone($host)
  {
    $headers = \get_curl_headers("/", "-H 'Host: $host'");
    $this->assertArrayHasKey('X-Robots-Tag', $headers);
    $this->assertEquals('none', $headers['X-Robots-Tag']);
  }

  /**
   * Ensure that X-Robots-Tag is set to all.
   *
   * @dataProvider provideValidHosts
   */
  public function testXRobotsAll($host)
  {
    $headers = \get_curl_headers("/", "-H 'Host: $host'");
    $this->assertArrayHasKey('X-Robots-Tag', $headers);
    $this->assertEquals('all', $headers['X-Robots-Tag']);
  }
}
