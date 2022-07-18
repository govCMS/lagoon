<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Traits;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Defines a class for media utilties in tests.
 */
trait MediaTrait {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * Create an image media entity.
   *
   * @param array $values
   *   Optional key => values to assign to the media entity.
   * @param \Drupal\file\Entity\File $file
   *   Optional file entity to use.
   *
   * @return \Drupal\media\Entity\Media
   *   A media entity.
   */
  protected function createImageMedia(array $values = [], File $file = NULL) {
    if (!$file) {
      $image = $this->getTestFiles('image')[0];
      $file = $this->createFile($image->uri);
    }

    $values = $values + [
      'bundle' => 'image',
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ];

    $media = Media::create($values);
    $media->save();
    return $media;
  }

  /**
   * Creates a file entity.
   *
   * @param string $uri
   *   The file uri.
   * @param int $status
   *   The file status.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   */
  protected function createFile($uri, $status = 1) {
    $file = File::create([
      'uri' => $uri,
      'status' => $status,
    ]);
    $file->save();
    return $file;
  }

}
