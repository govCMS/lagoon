<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that bots are not allowed.
 */
class RobotsTxtTest extends TestCase
{

  public function provideHosts()
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
   * Disallow in govcms.
   *
   * @dataProvider provideHosts
   */
  public function testDisallowString($host)
  {
    $robots_txt = \curl_get_content("/robots.txt", "-H 'Host: $host'");
    $this->assertEquals($robots_txt[0], "User-agent: *");
    $this->assertEquals($robots_txt[1], 'Disallow: /');
  }

  /**
   * Govcms skips.
   */
  public function testGovcmsSkips()
  {
    // Drupal is configured to respond to robots.txt as well as we're adding X-Robots-Tag to all Drupal requests.
    // We ensure that X-Robots-Tag is added to the response as this means Drupal is serving the request.
    $robots_headers = get_curl_headers("/robots.txt", "-H 'Host: www.govcms.gov.au'");
    $this->assertArrayHasKey('X-Robots-Tag', $robots_headers);
  }
}
