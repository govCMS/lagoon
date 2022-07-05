<?php

namespace Drupal\key\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a key input annotation object.
 *
 * @Annotation
 */
class KeyInput extends Plugin {

  /**
   * The plugin ID of the key input.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the key input.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the key input.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
