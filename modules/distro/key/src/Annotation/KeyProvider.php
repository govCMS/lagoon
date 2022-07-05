<?php

namespace Drupal\key\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a key provider annotation object.
 *
 * @Annotation
 */
class KeyProvider extends Plugin {

  /**
   * The plugin ID of the key provider.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the key provider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the key provider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The storage method of the key provider.
   *
   * This is an enumeration of {file, config, database, remote}.
   *
   * @var string
   */
  public $storage_method;

  /**
   * The settings for inputting a key value.
   *
   * This is used to indicate to the key input plugin if this provider
   * accepts a key value and if it requires one.
   *
   * @var array
   */
  public $key_value = [
    'accepted' => FALSE,
    'required' => FALSE,
  ];

}
