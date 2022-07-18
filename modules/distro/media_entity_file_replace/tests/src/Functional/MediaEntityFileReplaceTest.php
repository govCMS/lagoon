<?php

namespace Drupal\Tests\media_entity_file_replace\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Class MediaEntityFileReplaceTest.
 *
 * @group media
 */
class MediaEntityFileReplaceTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field_ui',
    'media',
    'media_entity_file_replace',
  ];

  /**
   * Tests basic functionality of the module.
   */
  public function testModule() {
    $this->createMediaType('file', [
      'id' => 'document',
      'label' => 'Document',
    ]);
    $this->createMediaType('oembed:video', [
      'id' => 'remote_video',
      'label' => 'Remote Video',
    ]);

    $user = $this->drupalCreateUser([
      'access media overview',
      'administer media form display',
      'view media',
      'administer media',
      'access content',
    ]);
    $this->drupalLogin($user);

    // Begin by confirming that our custom file replacement widget is available
    // on form display configurations for media bundles that use a file source.
    $this->drupalGet('/admin/structure/media/manage/document/form-display');
    $this->assertSession()->fieldExists("fields[replace_file][region]");
    $this->assertSession()->fieldValueEquals('fields[replace_file][region]', 'hidden');

    // But not on media bundles that don't use a file source, like remote video.
    $this->drupalGet('/admin/structure/media/manage/remote_video/form-display');
    $this->assertSession()->fieldNotExists("fields[replace_file][weight]");
    // While we're here, enable the name field so we can manually provide a name
    // for remote videos. This just makes tests easier.
    $page = $this->getSession()->getPage();
    $page->fillField('fields[name][region]', 'content');
    $page->pressButton('Save');

    // Create a video media entity and confirm we don't see the replacement
    // widget on the edit screen.
    $this->drupalGet('/media/add/remote_video');
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextNotContains('Replace file');
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $page->fillField('Name', 'DrupalCon Amsterdam Keynote');
    $page->fillField('Remote video URL', 'https://www.youtube.com/watch?v=Apqd4ff0NRI');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Remote video DrupalCon Amsterdam Keynote has been created.');
    $page->clickLink('DrupalCon Amsterdam Keynote');
    $this->assertSession()->fieldExists('Remote video URL');
    $this->assertSession()->fieldNotExists('files[replacement_file]');

    // Create a document entity and confirm it works as usual.
    // The file replacement widget should not appear on this form since we did
    // not enable the new replacement widget on the form display yet.
    $uri = 'temporary://foo.txt';
    file_put_contents($uri, 'original');
    $this->drupalGet('/media/add/document');
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextNotContains('Replace file');
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/media');
    unlink($uri);

    // Edit the document and confirm the remove button for the default file
    // widget is there, since our pseudo widget which normally removes it is not
    // yet active.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->assertSession()->buttonExists('Remove');

    // Now enable the file replacement widget for document media bundle.
    $this->drupalGet('/admin/structure/media/manage/document/form-display');
    $page = $this->getSession()->getPage();
    $page->fillField('fields[replace_file][region]', 'content');
    $page->pressButton('Save');

    // Edit the document again. The "remove" button on the default file
    // widget should be removed now.
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->assertSession()->buttonNotExists('Remove');

    // And there should be additional fields for uploading replacement file and
    // controlling behavior for overwriting it.
    $this->assertSession()->fieldExists('files[replacement_file]');
    $this->assertSession()->fieldExists('keep_original_filename');

    // Upload a replacement file with new contents, overwriting the original
    // file.
    $originalFile = $this->loadFileEntity(($originalDocument->getSource()->getSourceFieldValue($originalDocument)));
    $uri = 'temporary://foo.txt';
    file_put_contents($uri, 'new');
    $page = $this->getSession()->getPage();
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->checkField('keep_original_filename');
    $page->pressButton('Save');
    unlink($uri);

    // Reload document and confirm the filename and URI have not changed, but
    // the contents of the file have.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $updatedFile = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertEquals($updatedFile->id(), $originalFile->id());
    $this->assertEquals($updatedFile->getFileUri(), $originalFile->getFileUri());
    $this->assertEquals($updatedFile->getFilename(), $originalFile->getFilename());
    $this->assertNotEquals($updatedFile->getSize(), $originalFile->getSize());
    $this->assertEquals(file_get_contents($updatedFile->getFileUri()), 'new');

    // Now upload another replacement document, but this time don't overwrite
    // the original.
    $originalDocument = $updatedDocument;
    $originalFile = $updatedFile;
    $uri = 'temporary://foo-new.txt';
    file_put_contents($uri, 'foo-new');
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $page = $this->getSession()->getPage();
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->uncheckField('keep_original_filename');
    $page->pressButton('Save');
    unlink($uri);

    // Verify that the file associated with the document is different than the
    // previous one.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $updatedFile = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertNotEquals($updatedFile->id(), $originalFile->id());
    $this->assertNotEquals($updatedFile->getFileUri(), $originalFile->getFileUri());
    $this->assertNotEquals($updatedFile->getFilename(), $originalFile->getFilename());
    $this->assertNotEquals($updatedFile->getSize(), $originalFile->getSize());
    $this->assertNotEquals(file_get_contents($updatedFile->getFileUri()), file_get_contents($originalFile->getFileUri()));
    $this->assertEquals(file_get_contents($updatedFile->getFileUri()), 'foo-new');
    $this->assertFalse($updatedFile->isTemporary());

    // The old file entity should still exist, and should not be marked as
    // temporary since editing the document entity created a revision and the
    // old revision still references the old document.
    $originalFile = $this->loadFileEntity($originalFile->id());
    $this->assertFalse($originalFile->isTemporary());

    // Verify that when uploading a replacement and overwriting the original,
    // the file extension is forced to be the same.
    // Now upload another replacement document, but this time don't overwrite
    // the original.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $uri = 'temporary://foo.pdf';
    file_put_contents($uri, 'pdf contents');
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $page = $this->getSession()->getPage();
    $this->assertSession()->fieldExists('files[replacement_file]');
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->checkField('keep_original_filename');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: txt');
    $this->assertSession()->addressEquals("/media/{$originalDocument->id()}/edit");
    // It should be allowed if we opt NOT to overwrite the original though.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $page = $this->getSession()->getPage();
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->uncheckField('keep_original_filename');
    $page->pressButton('Save');
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed: txt');
    $this->assertSession()->addressEquals("/admin/content/media");
    $this->assertSession()->pageTextNotContains('foo.pdf');
    unlink($uri);

    // Simulate deleting the file and then revisit the media entity. Since
    // there is no longer a file associated to the media entity, there is
    // nothing to replace and therefore the replace file widget should not show.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $fileToDelete = $this->loadFileEntity($originalDocument->getSource()->getSourceFieldValue($originalDocument));
    $fileToDelete->delete();
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $page = $this->getSession()->getPage();
    $this->assertSession()->fieldNotExists('files[replacement_file]');
  }

  /**
   * Load a single media entity by name, ignoring object cache.
   */
  protected function loadMediaEntityByName($name) {
    $mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
    $mediaStorage->resetCache();
    $entities = $mediaStorage->loadByProperties(['name' => $name]);
    $this->assertNotEmpty($entities, "No media entity with name $name was found.");
    return array_pop($entities);
  }

  /**
   * Load a single file entity by ID, ignoring object cache.
   */
  protected function loadFileEntity($id) {
    $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    $fileStorage->resetCache();
    $file = $fileStorage->load($id);
    $this->assertNotNull($file, "No file entity with id $id was found.");
    return $file;
  }

}
