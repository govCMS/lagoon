<?php

namespace Drupal\menu_trail_by_path\Menu;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuTreeStorageInterface;

class MenuTreeStorageMenuHelper implements MenuHelperInterface {
  /**
   * @var MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * @var MenuTreeStorageInterface
   */
  protected $menuTreeStorage;

  /**
   * MenuTreeStorageMenuHelper constructor.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $menu_tree_storage
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, MenuTreeStorageInterface $menu_tree_storage) {
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTreeStorage = $menu_tree_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuLinks($menu_name) {
    // nice to have: implement filtering like public/core/lib/Drupal/Core/Menu/MenuLinkTree.php:153
    $menu_links    = [];
    $menu_plugins = $this->menuTreeStorage->loadByProperties(['menu_name' => $menu_name]);
    foreach ($menu_plugins as $plugin_id => $definition) {
      $menu_links[$plugin_id] = $this->menuLinkManager->createInstance($plugin_id);
    }
    return $menu_links;
  }
}
