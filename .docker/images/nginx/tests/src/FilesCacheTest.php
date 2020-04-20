<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

class FilesCacheTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    `docker-compose exec nginx mkdir -p /app/sites/default/files`;
    foreach ($this->providerExpiredHeaderPath() as $parts) {
      list($file, $path) = $parts;
      `docker cp $path/$file $(docker-compose ps -q nginx):/app/web/sites/default/files/`;
    }
  }

  /**
   * Return a list of files to test.
   *
   * @return array
   *   File list.
   */
  public function providerExpiredHeaderPath() {
    $path = dirname(__DIR__);
    return [
      ['autotest.jpg', "$path/resources/", 'max-age=2628001'],
      ['autotest.pdf', "$path/resources/", 'max-age=1800'],
      ['autotest.rtf', "$path/resources/", 'max-age=2628001'],
    ];
  }

  /**
   * Ensure that expires headers are correctly set.
   *
   * @dataProvider providerExpiredHeaderPath
   */
  public function testExpiredHeaderPath($file, $path, $expected) {
    $path = "/sites/default/files/$file";
    $headers = \get_curl_headers($path);
    $this->assertEquals($expected, $headers['Cache-Control']);
  }

}
