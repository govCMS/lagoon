<?php

namespace Drupal\crop\Events;

use Drupal\Core\Image\ImageInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\Entity\CropType;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents automatic crop action as event.
 */
class AutomaticCrop extends Event {

  /**
   * The crop entity.
   *
   * @var \Drupal\crop\CropInterface|false
   */
  protected $crop = FALSE;


  /**
   * The image resource to crop.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

  /**
   * The crop type loaded.
   *
   * @var \Drupal\crop\Entity\CropType
   */
  protected $cropType;

  /**
   * All data required by crop providers.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a EntitySelectionEvent object.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   * @param \Drupal\crop\Entity\CropType $cropType
   * @param $configuration
   */
  public function __construct(ImageInterface $image, CropType $cropType, array $configuration) {
    $this->image = $image;
    $this->cropType = $cropType;
    $this->configuration = $configuration;
  }

  /**
   * Set calculated crop instance.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop entity instance.
   */
  public function setCrop(CropInterface $crop) {
    $this->crop = $crop;
  }

  /**
   * Get crop instance.
   *
   * @return \Drupal\crop\CropInterface|false
   *   List of fallbacks.
   */
  public function getCrop() {
    return $this->crop;
  }

  /**
   * Get the crop type entity.
   *
   * @return \Drupal\crop\Entity\CropType
   *   The crop type entity loaded.
   */
  public function getCropType() {
    return $this->cropType;
  }

  /**
   * Get image to crop.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image resource.
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * Get all configurations to generate automatic crop.
   *
   * @return array
   *   All data to be used by automatic crop providers.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

}
