<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Test frame options from the request.
 */
class RobotsTest extends TestCase
{

  /**
   * Test various subdomain patterns.
   */
  public function provideSubdomains()
  {
    return [
      ['test'],
      ['another-subdomain'],
      ['classification'],
    ];
  }

  /**
   * Ensure that *.govcms.gov.au gets a disallow header.
   *
   * @dataProvider provideSubdomains
   */
  public function testDisallow($subdomain)
  {
    $content = \curl_get_content("/robots.txt", '--header "Host: ' . $subdomain . '.govcms.gov.au"');
    $expected = explode("\n", "User-agent: *\nDisallow: /");
    $this->assertEquals($expected, $content);
  }

  /**
   * Ensure that www.govcms.gov.au can access robots.
   */
  public function testAllowGovCMS()
  {
    $content = \curl_get_content("/robots.txt", '--header "Host: www.govcms.gov.au"');
    $expected = explode("\n", "User-agent: *\nDisallow: /");
    var_dump($content);
    $this->assertNotEquals($expected, $content);
  }
}
