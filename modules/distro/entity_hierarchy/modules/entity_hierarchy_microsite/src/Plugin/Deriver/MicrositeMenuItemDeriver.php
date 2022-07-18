<?php

namespace Drupal\entity_hierarchy_microsite\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\entity_hierarchy_microsite\MicrositeMenuLinkDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for deriving menu links from a tree.
 */
class MicrositeMenuItemDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Link discovery.
   *
   * @var \Drupal\entity_hierarchy_microsite\MicrositeMenuLinkDiscoveryInterface
   */
  private $micrositeMenuLinkDiscovery;

  /**
   * Constructs a new MicrositeMenuItemDeriver.
   *
   * @param \Drupal\entity_hierarchy_microsite\MicrositeMenuLinkDiscoveryInterface $micrositeMenuLinkDiscovery
   *   Link discovery.
   */
  public function __construct(MicrositeMenuLinkDiscoveryInterface $micrositeMenuLinkDiscovery) {
    $this->micrositeMenuLinkDiscovery = $micrositeMenuLinkDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_hierarchy_microsite.menu_link_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = $this->micrositeMenuLinkDiscovery->getMenuLinkDefinitions();
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
