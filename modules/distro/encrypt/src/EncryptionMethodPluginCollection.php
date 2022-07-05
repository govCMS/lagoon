<?php

namespace Drupal\encrypt;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading EncryptionMethod plugins.
 */
class EncryptionMethodPluginCollection extends DefaultSingleLazyPluginCollection {

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
