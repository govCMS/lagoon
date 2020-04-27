<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

/**
 * Ensure that the private files are restricted.
 */
class PrivateFilesTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // Make sure private files directory exists in the nginx container.
    `docker-compose exec nginx mkdir -p /app/web/sites/default/files/private`;
    foreach ($this->providerFileAccess() as $parts) {
      list($file, $path) = $parts;
      // Move out test files.
      `docker cp $path/$file $(docker-compose ps -q nginx):/app/web/sites/default/files/private/`;
    }
  }

  /**
   * Return a list of files to test.
   *
   * @return array
   *    File list.
   */
  public function providerFileAccess() {
    $path = dirname(__DIR__);
    return [
      ['autotest.jpg', "$path/resources/"],
      ['autotest.pdf', "$path/resources/"],
      ['autotest.rtf', "$path/resources/"],
    ];
  }

  /**
   * Ensure that private files are restricted.
   *
   * @dataProvider providerFileAccess
   */
  public function testFileAccess($file) {
    $path = "/sites/default/files/private/$file";
    $headers = \get_curl_headers($path);
    $this->assertEquals(404, $headers['Status'], "[$path] is publicly accessible");
  }

}
