<?php

namespace Drupal\focal_point\Plugin\ImageEffect;

use Drupal\focal_point\FocalPointEffectBase;
use Drupal\Core\Image\ImageInterface;

/**
 * Crops image while keeping its focal point as close to centered as possible.
 *
 * @ImageEffect(
 *   id = "focal_point_crop",
 *   label = @Translation("Focal Point Crop"),
 *   description = @Translation("Crops image while keeping its focal point as close to centered as possible.")
 * )
 */
class FocalPointCropImageEffect extends FocalPointEffectBase {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function applyEffect(ImageInterface $image) {
    parent::applyEffect($image);

    $crop = $this->getCrop($image);
    return $this->applyCrop($image, $crop);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Include a `crop_type` so that the crop module can act on images
    // generated using this effect.
    // @see crop_file_url_alter()
    // @see https://www.drupal.org/node/2929502
    return parent::defaultConfiguration() + [
      'crop_type' => 'focal_point',
    ];
  }

}
