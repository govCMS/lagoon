<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that the blocks are respected by nginx.
 */
class BlocksTest extends TestCase {

  /**
   * Aggressive bots.
   *
   * @return array
   *   List of user agents.
   */
  public function providerAggressiveAgents() {
    return [
      ['8LEGS'],
      // ['AhfresBot'], # This 500s?
      ['Exabot'],
      ['HTTrack'],
      ['ltx71'],
      ['MJ12bot'],
      ['OpenLinkProfiler'],
      ['Pcore-HTTP'],
      ['TurnitinBot'],
      ['YandexBot'],
      ['disco_crawl'],
    ];
  }

  /**
   * Known Microsoft user agents.
   *
   * @return array
   *    List of user agents.
   */
  public function providerMicrosoftAgents() {
    return [
      ['Skype.for.Business'],
      ['Microsoft.Office'],
    ];
  }

  /**
   * Common wordpress paths that could be vulnerable.
   *
   * @return array
   *   List of paths.
   */
  public function providerWordpressPaths() {
    return [
      ['/wp-admin'],
      // ['/wp-admin/index.php'], # 500s
      ['/wp-admin/posts'],
      ['/wp-content'],
      ['/wp-content/path/to/content'],
      ['/wp-includes'],
      ['/wp-json'],
      // ['/wp-login/index.php'], # 500s
      // ['/wp-mail.php'], # 500s
      // ['/wp-mail.php?query=malicious_string'], # 500s
    ];
  }

  /**
   * Common query strings.
   *
   * @return array
   *    List of query strings.
   */
  public function providerQueryStrings() {
    return [
      ['?q=node/add'],
      ['?q=user/register'],
    ];
  }

  /**
   * Ensure that aggressive bots are blocked.
   *
   * @dataProvider providerAggressiveAgents
   */
  public function testAggressiveCrawlerBlock($ua) {
    $headers = \get_curl_headers("/", "--user-agent '{$ua}'");
    $this->assertEquals(403, $headers['Status']);
  }

  /**
   * Ensure that Microsofts home check is prevented.
   *
   * @dataProvider providerMicrosoftAgents
   */
  public function testMicrosoftHomeCall($ua) {
    $headers = \get_curl_headers("/", "--user-agent '{$ua}'");
    $this->assertEquals(403, $headers['Status']);
  }

  /**
   * Ensure the autodiscover.xml files are restricted.
   */
  public function testAutodiscover() {
    $headers = \get_curl_headers("/autodiscover.xml");
    $this->assertEquals(403, $headers['Status']);
  }

  /**
   * Ensure that wordpress-like paths are blocked.
   *
   * @dataProvider providerWordpressPaths
   */
  public function testWordpressAttacks($path) {
    $headers = \get_curl_headers($path);
    $this->assertEquals(403, $headers['Status']);
  }

  /**
   * Ensure common query strings vectors are restricted.
   *
   * @dataProvider providerQueryStrings
   */
  public function testQueryStringBlock($query) {
    $headers = \get_curl_headers($query);
    $this->assertEquals(403, $headers['Status']);
  }

}
