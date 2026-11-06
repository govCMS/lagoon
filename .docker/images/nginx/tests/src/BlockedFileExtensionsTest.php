<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that nginx refuses to serve blocked file types.
 */
class BlockedFileExtensionsTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $resource = dirname(__DIR__) . '/resources/autotest.rtf';
    `docker compose exec nginx mkdir -p /app/web/sites/default/files`;
    foreach ($this->providerBlockedFiles() as $parts) {
      [$file] = $parts;
      `docker cp $resource $(docker compose ps -q nginx):/app/web/sites/default/files/$file`;
    }
  }

  /**
   * Return a list of blocked files.
   *
   * @return array
   *   File list.
   */
  public function providerBlockedFiles() {
    return [
      ['autotest.doc'],
      ['autotest.xls'],
      ['autotest.ppt'],
      ['autotest.rtf'],
      ['autotest.DOC'],
    ];
  }

  /**
   * Ensure that public files with a blocked extension are not served.
   *
   * @dataProvider providerBlockedFiles
   */
  public function testPublicFileBlocked($file) {
    $path = "/sites/default/files/$file";
    $headers = \get_curl_headers($path);
    $this->assertEquals(403, $headers['Status'], "[$path] is served");
  }

  /**
   * Ensure that private files with a blocked extension are not served.
   *
   * @dataProvider providerBlockedFiles
   */
  public function testPrivateFileBlocked($file) {
    foreach (["/system/files/$file", "/system/files?file=$file"] as $path) {
      $headers = \get_curl_headers($path);
      $this->assertEquals(403, $headers['Status'], "[$path] is not blocked");
    }
  }

  /**
   * Ensure that other file types are still served.
   */
  public function testAllowedFileServed() {
    $path = dirname(__DIR__) . '/resources';
    `docker cp $path/autotest.pdf $(docker compose ps -q nginx):/app/web/sites/default/files/`;
    $headers = \get_curl_headers('/sites/default/files/autotest.pdf');
    $this->assertEquals(200, $headers['Status']);
  }

}
