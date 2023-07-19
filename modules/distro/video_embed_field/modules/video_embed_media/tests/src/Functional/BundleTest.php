<?php

namespace Drupal\Tests\video_embed_media\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\video_embed_field\Functional\AdminUserTrait;

/**
 * Test the video_embed_field media integration.
 *
 * @group video_embed_media
 */
class BundleTest extends MediaFunctionalTestBase {

  use AdminUserTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'video_embed_field',
    'video_embed_media',
  ];

  /**
   * Test the dialog form.
   */
  public function testMediaBundleCreation() {
    $this->drupalLogin($this->adminUser);

    // Create bundle and modify form display.
    $media_type = $this->createMediaType('video_embed_field', ['id' => 'video_bundle']);
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);
    if ($source_field->isDisplayConfigurable('form')) {
      // Use the default widget and settings.
      $component = \Drupal::service('plugin.manager.field.widget')
        ->prepareConfiguration('video_embed_field', []);

      // @todo Replace entity_get_form_display() when #2367933 is done.
      // https://www.drupal.org/node/2872159.
      $this->container->get('entity_display.repository')->getFormDisplay('media', $media_type->id(), 'default')
        ->setComponent($source_field->getName(), $component)
        ->save();
    }

    // Ensure the video field is added to the media entity.
    $this->drupalGet('admin/structure/media/manage/video_bundle/fields');
    $this->assertSession()->pageTextContains('field_media_video_embed_field');
    $this->assertSession()->pageTextContains('Video URL');

    // Add a media entity with the new field.
    $this->drupalGet('media/add/video_bundle');
    $this->submitForm([
      'name[0][value]' => 'Drupal video!',
      'field_media_video_embed_field[0][value]' => 'https://www.youtube.com/watch?v=XgYu7-DQjDQ',
    ], 'Save');
    // We should see the video thumbnail on the media page.
    $this->assertStringContainsString('video_thumbnails/XgYu7-DQjDQ.jpg', $this->getSession()->getPage()->getHtml());
  }

}
