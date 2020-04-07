<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that bots are not allowed.
 */
class RobotsTxtTest extends TestCase {

  /**
   * List of disallowed hosts.
   *
   * @return array
   *   Array of disallowed hosts.
   */
  public function providerDisallowedHosts() {
    return [
      ['test.govcms.gov.au'],
      ['wsa.govcms.gov.au'],
      ['multi.subdomain.govcms.gov.au'],
      ['www2.govcms.gov.au'],
      ['test.govcms.gov.au'],
    ];
  }

  /**
   * Test that robots.txt returns correct Disallow directive for provided hosts.
   *
   * @dataProvider providerDisallowedHosts
   */
  public function testDisallowedHosts($host) {
    $robots_txt = \curl_get_content('/robots.txt', "-H 'Host: $host'");
    $this->assertEquals('User-agent: *', $robots_txt[0]);
    $this->assertEquals('Disallow: /', $robots_txt[1]);
  }

  /**
   * Govcms skips.
   */
  public function testGovcmsSkips() {
    // Drupal is configured to respond to robots.txt as well as we're
    // adding "X-Robots-Tag" to all Drupal requests.
    // We ensure that "X-Robots-Tag" is added to the response as this means
    // that Drupal is serving the request.
    $robots_headers = \get_curl_headers('/robots.txt', "-H 'Host: www.govcms.gov.au'");
    $this->assertArrayHasKey('X-Robots-Tag', $robots_headers);
  }

}
