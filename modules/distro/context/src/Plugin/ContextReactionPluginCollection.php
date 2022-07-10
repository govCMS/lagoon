<?php

namespace Drupal\context\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Provide a class to get a plugin instance.
 */
class ContextReactionPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
