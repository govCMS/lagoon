<?php

namespace Drupal\Tests\focal_point\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that the Focal Point widget works properly.
 *
 * @group focal_point
 */
class FocalPointWidgetTest extends BrowserTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'focal_point'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

  }

  /**
   * {@inheritDoc}
   */
  public function testResave() {

    $field_name = strtolower($this->randomMachineName());

    $this->createImageField($field_name, 'article', [], [
      'file_extensions' => 'png jpg gif',
    ], [], [
      'image_style' => 'large',
      'image_link' => '',
    ]);

    // Find images that match our field settings.
    $images = $this->getTestFiles('image');

    // Create a File entity for the initial image.
    $file = File::create([
      'uri' => $images[0]->uri,
      'uid' => 0,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();

    // Use the first valid image to create a new Node.
    $image_factory = $this->container->get('image.factory');
    $image = $image_factory->get($images[0]->uri);

    /** @var \Drupal\focal_point\FocalPointManagerInterface $focalPointManager */
    $focalPointManager = \Drupal::service('focal_point.manager');

    $crop = $focalPointManager->getCropEntity($file, 'focal_point');
    $focalPointManager->saveCropEntity(5, 5, $image->getWidth(), $image->getHeight(), $crop);

    $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
      $field_name => [
        'target_id' => $file->id(),
        'width' => $image->getWidth(),
        'height' => $image->getHeight(),
      ],
    ]);

    $crop = $focalPointManager->getCropEntity($file, 'focal_point');

    $this->assertEquals(2, $crop->get('x')->value);
    $this->assertEquals(1, $crop->get('y')->value);
    $this->assertEquals(0, $crop->get('width')->value);
    $this->assertEquals(0, $crop->get('height')->value);
  }

}
