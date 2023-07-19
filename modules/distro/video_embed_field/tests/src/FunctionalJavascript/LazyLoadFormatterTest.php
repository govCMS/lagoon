<?php

namespace Drupal\Tests\video_embed_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\video_embed_field\Functional\EntityDisplaySetupTrait;

/**
 * Test the lazy load formatter.
 *
 * @group video_embed_field
 */
class LazyLoadFormatterTest extends WebDriverTestBase {

  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'video_embed_field',
    'video_embed_field_mock_provider',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupEntityDisplays();
  }

  /**
   * Test the lazy load formatter.
   */
  public function testColorboxFormatter() {
    $this->setDisplayComponentSettings('video_embed_field_lazyload', [
      'autoplay' => TRUE,
      'responsive' => TRUE,
    ]);
    $node = $this->createVideoNode('https://example.com/mock_video');
    $this->drupalGet('node/' . $node->id());
    $this->click('.video-embed-field-lazy');
    $this->assertSession()->elementExists('css', '.video-embed-field-lazy .video-embed-field-responsive-video');
    // Make sure the right library files are loaded on the page.
    $this->assertSession()->elementExists('css', 'link[href*="video_embed_field.responsive-video.css"]');
  }

}
