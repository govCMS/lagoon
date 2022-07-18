<?php

namespace Drupal\menu_trail_by_path;

use \Drupal\Core\DependencyInjection\ServiceProviderBase;
use \Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the class for the menu link tree.
 */
class MenuTrailByPathServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.active_trail');
    $definition->setClass('Drupal\menu_trail_by_path\MenuTrailByPathActiveTrail');
    $definition->addArgument(new Reference('menu_trail_by_path.path_helper'));
    $definition->addArgument(new Reference('menu_trail_by_path.menu_helper'));
    $definition->addArgument(new Reference('router.request_context'));
    $definition->addArgument(new Reference('language_manager'));
    $definition->addArgument(new Reference('config.factory'));
  }
}
