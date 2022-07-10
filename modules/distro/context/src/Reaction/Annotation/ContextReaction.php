<?php

namespace Drupal\context\Reaction\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an context reaction annotation object.
 *
 * Plugin Namespace: Plugin\ContextReaction.
 *
 * @Annotation
 */
class ContextReaction extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the context reaction.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the context reaction.
   *
   * This will be shown when adding or configuring this context reaction.
   *
   * @var \Drupal\Core\Annotation\Translationoptional
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
