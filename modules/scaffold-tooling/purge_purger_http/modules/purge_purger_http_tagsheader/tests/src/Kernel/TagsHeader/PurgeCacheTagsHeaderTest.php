<?php

namespace Drupal\Tests\purge_purger_http\Kernel\TagsHeader;

use Drupal\Tests\purge\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\purge_purger_http_tagsheader\Plugin\Purge\TagsHeader\PurgeCacheTagsHeader.
 *
 * @group purge_purger_http
 */
class PurgeCacheTagsHeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'purge_purger_http_tagsheader'];

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test that the header value is exactly as expected (space separated).
   */
  public function testHeaderValue(): void {
    $request = Request::create('/system/401');
    $response = $this->container->get('http_kernel')->handle($request);
    $tags_header = $response->headers->get('Purge-Cache-Tags');
    $tags = explode(' ', $tags_header);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertTrue(is_string($tags_header));
    $this->assertTrue(strlen($tags_header) > 5);
    $this->assertTrue(in_array('config:user.role.anonymous', $tags));
    $this->assertTrue(in_array('http_response', $tags));
    $this->assertTrue(in_array('rendered', $tags));
  }

}
