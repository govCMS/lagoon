<?php

namespace Drupal\Tests\embed\Kernel;

use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\Entity\EmbedButton;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests embed button icon file handling.
 *
 * @group embed
 * @coversDefaultClass \Drupal\embed\Entity\EmbedButton
 */
class IconTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'embed',
    'embed_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('system');
    $this->installEntitySchema('embed_button');
  }

  /**
   * Tests the icon functionality.
   *
   * @covers ::convertImageToEncodedData
   * @covers ::convertEncodedDataToImage
   * @covers ::getIconUrl
   */
  public function testIcon() {
    $button = EmbedButton::create([
      'id' => 'test',
      'label' => 'Test',
      'type_id' => 'embed_test_default',
    ]);
    $this->assertEmpty($button->icon);
    $this->assertIconUrl('/default.png', $button);

    $uri = 'public://button.png';
    $image_contents = file_get_contents('core/misc/favicon.ico');
    file_put_contents($uri, $image_contents);

    $button->set('icon', EmbedButton::convertImageToEncodedData($uri));
    $this->assertSame([
      'data' => base64_encode($image_contents),
      'uri' => $uri,
    ], $button->icon);
    $this->assertIconUrl($uri, $button);

    // Delete the file and call getIconUrl and test that it recreated the file.
    unlink($uri);
    $this->assertFalse(is_file($uri));
    $this->assertIconUrl($uri, $button);
    $this->assertTrue(is_file($uri));
    $this->assertSame(file_get_contents($uri), file_get_contents('core/misc/favicon.ico'));

    // Test a manual, external URL for the icon image.
    $button->set('icon', [
      'uri' => 'http://www.example.com/button.png',
    ]);
    $this->assertIconUrl('http://www.example.com/button.png', $button);
  }

  /**
   * Test a button's icon URL.
   *
   * @param string $uri
   *   The exepcted URI to the icon file.
   * @param \Drupal\embed\EmbedButtonInterface $button
   *   The embed button.
   * @param string $message
   *   The assertion message.
   */
  protected function assertIconUrl($uri, EmbedButtonInterface $button, string $message = '') {
    $this->assertSame(file_url_transform_relative(file_create_url($uri)), $button->getIconUrl(), $message);
  }

}
