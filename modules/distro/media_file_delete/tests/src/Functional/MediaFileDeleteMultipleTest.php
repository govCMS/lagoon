<?php

namespace Drupal\Tests\media_file_delete\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test for deleting multiple files for media_file_delete module.
 *
 * @group media_file_delete
 */
class MediaFileDeleteMultipleTest extends BrowserTestBase {

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
    'media_test_views',
  ];

  /**
   * Tests media deletion.
   */
  public function testMediaFileDeleteMultiple() {
    $image_type = $this->createMediaType('image');
    $image1 = $this->getTestFiles('image')[0];
    $image2 = $this->getTestFiles('image')[1];
    $image3 = $this->getTestFiles('image')[2];
    assert($image1 instanceof \stdClass);
    assert(property_exists($image1, 'uri'));
    assert($image2 instanceof \stdClass);
    assert(property_exists($image2, 'uri'));
    assert($image3 instanceof \stdClass);
    assert(property_exists($image3, 'uri'));
    $editor1 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
      'access media overview',
      'view media',
    ]);
    $editor2 = $this->createUser([
      sprintf('delete any %s media', $image_type->id()),
      'delete any file',
      'access media overview',
      'view media',
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
    $file3 = File::create([
      'uri' => $image3->uri,
      'status' => 1,
      'uid' => $editor1->id(),
    ]);
    $file3->save();
    $media4 = Media::create([
      'bundle' => $image_type->id(),
      'name' => $this->randomMachineName(),
      'field_media_image' => $file3,
    ]);
    $media4->save();

    $this->drupalLogin($editor1);
    $this->drupalGet('test-media-bulk-form');
    $assert = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $page->checkField('media_bulk_form[0]');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $assert->fieldExists('also_delete_file');
    $page->checkField('also_delete_file');
    $page->pressButton('Delete');
    $assert->pageTextContains('Deleted 1 item.');
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media1->id()));
    // File1 is also in use in media3.
    $assert->pageTextContains('Could not delete 1 associated file because it is used in other places');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file1->id()));

    $page->checkField('media_bulk_form[0]');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $page->checkField('also_delete_file');
    $page->pressButton('Delete');
    $assert->pageTextContains('Deleted 1 item.');
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media2->id()));
    // The editor1 does not have permission to delete file created by editor2.
    $assert->pageTextContains('Could not delete 1 associated file because of insufficient privilege.');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file2->id()));

    $this->drupalLogin($editor2);
    $this->drupalGet('test-media-bulk-form');
    $assert = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $page->checkField('also_delete_file');
    $page->pressButton('Delete');
    $assert->pageTextContains('Deleted 2 items.');
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media3->id()));
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($media4->id()));
    $assert->pageTextContains('Deleted 2 associated files.');
    // Usage count of file1 is now 0 because both media1 and 3 were deleted.
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file1->id()));
    // Editor2 can delete any file regardless of file owner.
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($file3->id()));
  }

}
