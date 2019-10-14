<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that bots are not allowed.
 */
class RobotsTxt extends TestCase
{

  public function provideHosts()
  {
    return [
      ['test.govcms.gov.au'],
      ['wsa.govcms.gov.au'],
      ['multi.subdomain.govcms.gov.au'],
      ['www2.govcms.gov.au'],
      ['test.govcms.gov.au'],
    ]
  }

  /**
   * Disallow in govcms.
   *
   * @dataProvider provideHosts
   */
  public function testDisallowString($host)
  {
    $robots_txt = curl_get_content("/robots.txt", "-H $host");
    $this->assertEquals($robots_txt, "User-agent: *\nDisallow: /");
  }

  /**
   * Govcms skips.
   */
  public function testGovcmsSkips()
  {
    $robots_txt = curl_get_content("/robots.txt", "-H www.govcms.gov.au");
    $this->assertNotEquals($robots_txt, "User-agent: *\nDisallow: /");
  }
}
