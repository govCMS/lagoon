<?php

namespace Drupal\Tests\media_file_delete\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a class for testing media_file_delete module.
 *
 * @group media_file_delete
 */
class MediaFileDeleteTest extends BrowserTestBase {

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
    'field',
    'user',
  ];

  /**
   * Tests media deletion.
   */
  public function testMediaFileDelete() {
    $image_type = $this->createMediaType('image');
    $image1 = $this->getTestFiles('image')[0];
    $image2 = $this->getTestFiles('image')[1];
    assert($image1 instanceof \stdClass);
    assert(property_exists($image1, 'uri'));
    assert($image2 instanceof \stdClass);
    assert(property_exists($image2, 'uri'));
    $editor1 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
    ]);
    $editor2 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
    ]);
    $editor3 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
      'delete any file',
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
    $file2 = File::create([
      'uri' => $image2->uri,
      'status' => 1,
      'uid' => $editor2->id(),
    ]);
    $file2->save();
    $media2 = Media::create([
      'bundle' => $image_type->id(),
      'name' => $this->randomMachineName(),
      'field_media_image' => $file2,
    ]);
    $media2->save();
    $media3 = Media::create([
      'bundle' => $image_type->id(),
      'name' => $this->randomMachineName(),
      'field_media_image' => $file1,
    ]);
    $media3->save();

    $this->drupalLogin($editor1);
    $this->drupalGet($media1->toUrl('delete-form'));
    $assert = $this->assertSession();

    // There are two usages here.
    $assert->fieldNotExists('also_delete_file');
    $assert->pageTextContains('The file attached to this media is used in 1 other place and will be retained.');

    $media3->delete();

    // Now there is one usage.
    $this->drupalGet($media1->toUrl('delete-form'));
    $assert->fieldExists('also_delete_file');
    $assert->pageTextNotContains('The file attached to this media is used in 1 other place and will be retained.');

    $this->submitForm([
      'also_delete_file' => 1,
    ], 'Delete');

    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media1->id()));
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file1->id()));

    $this->drupalGet($media2->toUrl('delete-form'));

    // The user does no have permission to delete this file.
    $assert->fieldNotExists('also_delete_file');
    $assert->pageTextContains(sprintf('The file attached to this media is owned by %s so will be retained.', $editor2->getDisplayName()));

    // This user has the 'delete any files' permission.
    $this->drupalLogin($editor3);
    $this->drupalGet($media2->toUrl('delete-form'));
    $assert->fieldExists('also_delete_file');
    $this->submitForm([
      'also_delete_file' => 1,
    ], 'Delete');
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media2->id()));
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file2->id()));
  }

}
