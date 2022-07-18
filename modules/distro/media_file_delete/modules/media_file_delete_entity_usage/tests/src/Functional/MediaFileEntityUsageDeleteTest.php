<?php

namespace Drupal\Tests\media_file_delete\Functional;

use Drupal\entity_usage\EntityUsageInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a class for testing media_file_delete module with entity usage.
 *
 * @group media_file_delete
 */
class MediaFileEntityUsageDeleteTest extends BrowserTestBase {

  use TestFileCreationTrait;
  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'media',
    'media_file_delete',
    'entity_usage',
    'media_file_delete_entity_usage',
    'field',
    'user',
  ];

  /**
   * Tests media deletion.
   */
  public function testMediaFileDeleteWithEntityUsage() {
    $image_type = $this->createMediaType('image');
    $image1 = $this->getTestFiles('image')[0];
    assert($image1 instanceof \stdClass);
    assert(property_exists($image1, 'uri'));
    $editor1 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
    ]);

    $file1 = File::create([
      'uri' => $image1->uri,
      'status' => 1,
      'uid' => $editor1->id(),
    ]);
    $file1->save();
    $media1 = Media::create([
      'bundle' => $image_type->id(),
      'name' => $this->randomMachineName(),
      'field_media_image' => $file1,
    ]);
    $media1->save();

    // Register an artificial usage.
    $entity_usage = \Drupal::service('entity_usage.usage');
    assert($entity_usage instanceof EntityUsageInterface);
    $random_user = $this->createUser();
    $entity_usage->registerUsage($file1->id(), 'file', $random_user->id(), 'user', $random_user->language()->getId(), $random_user->getRevisionId(), 'html_link', 'name');

    $this->drupalLogin($editor1);
    $this->drupalGet($media1->toUrl('delete-form'));
    $assert = $this->assertSession();

    // There are two usages here.
    $assert->fieldNotExists('also_delete_file');
    $assert->pageTextContains('The file attached to this media is used in 1 other place and will be retained.');
    $random_user->delete();

    // Now there is one usage.
    $this->drupalGet($media1->toUrl('delete-form'));
    $assert->fieldExists('also_delete_file');
    $assert->pageTextNotContains('The file attached to this media is used in 1 other place and will be retained.');

    $this->submitForm([
      'also_delete_file' => 1,
    ], 'Delete');

    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media1->id()));
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file1->id()));
  }

}
