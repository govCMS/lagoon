<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading key plugins.
 */
class KeyPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function addInstanceId($id, $configuration = NULL) {
    $this->instanceId = $id;
    parent::addInstanceId($id, $configuration);
    if ($configuration !== NULL) {
      $this->setConfiguration($configuration);
    }
  }

}
