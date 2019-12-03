<?php

namespace GovCMSTests;

use PHPUnit\Framework\TestCase;

class FilesCacheTest extends TestCase
{

  /**
   * Return a list of files to test.
   *
   * @return array
   *   File list.
   */
  public function filesProvider()
  {
    $path = dirname(__DIR__);
    return [
      ["autotest.jpg", "$path/resources/", 'max-age=2628001'],
      ["autotest.pdf", "$path/resources/", 'max-age=1800'],
      ["autotest.rtf", "$path/resources/", 'max-age=2628001'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void
  {
    `docker-compose exec nginx mkdir -p /app/sites/default/files`;
    foreach ($this->filesProvider() as $parts) {
      list($file, $path) = $parts;
      `docker cp $path/$file $(docker-compose ps -q nginx):/app/web/sites/default/files/`;
    }
  }

  /**
   * Ensure that expires headers are correctly set.
   *
   * @dataProvider filesProvider
   */
  public function testHeader($file, $path, $expected)
  {
    $path = "/sites/default/files/$file";
    $headers = \get_curl_headers($path);
    $this->assertEquals($expected, $headers['Cache-Control']);
  }
}
