<?php

namespace Drupal\Tests\focal_point\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests Focal Point's integration with Media Library.
 *
 * @group focal_point
 */
class MediaLibraryIntegrationTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'media_library',
    'focal_point',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
    ]);
    $this->createMediaType('image', [
      'id' => 'image',
    ]);

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_image',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Image',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
          ],
        ],
      ],
    ])->save();

    // Ensure that the media type is using Focal Point in its media library
    // form display.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = $this->container->get('entity_display.repository');
    $display_repository->getFormDisplay('media', 'image', 'media_library')
      ->setComponent('field_media_image', [
        'type' => 'image_focal_point',
        'settings' => [
          'preview_image_style' => 'media_library',
        ],
      ])
      ->save();
    // Ensure that the media field on the Article content type is using the
    // media library.
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_image', [
        'type' => 'media_library_widget',
      ])
      ->save();

    $user = $this->drupalCreateUser([
      'create article content',
      'create media',
      'access media overview',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests Focal Point integration with the media library.
   */
  public function testFocalPointMediaField() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $files = $this->getTestFiles('image');
    $path = $this->container->get('file_system')->realpath($files[0]->uri);
    $this->assertNotEmpty($path);

    // Upload an image and ensure that a single Focal Point widget shows up.
    $this->drupalGet('/node/add/article');
    $page->pressButton('Add media');
    $file_field = $assert_session->waitForField('Add file');
    $this->assertNotEmpty($file_field);
    $file_field->attachFile($path);

    $widget_exists = $this->getSession()
      ->getPage()
      ->waitFor(10, function (DocumentElement $page) {
        $elements = $page->findAll('css', '[data-media-library-added-delta] .focal-point-indicator');
        return count($elements) === 1;
      });
    $this->assertTrue($widget_exists);
  }

}
