<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface;

/**
 * Defines a class for microsite menu link discovery.
 */
interface MicrositeMenuLinkDiscoveryInterface {

  /**
   * Gets menu link definitions for the given site or all sites if none given.
   *
   * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface|null $microsite
   *   Microsite.
   *
   * @return array
   *   Menu link plugin definitions.
   */
  public function getMenuLinkDefinitions(MicrositeInterface $microsite = NULL);

}
