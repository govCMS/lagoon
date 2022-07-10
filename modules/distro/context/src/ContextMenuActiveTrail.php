<?php

namespace Drupal\context;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Extend the MenuActiveTrail class.
 */
class ContextMenuActiveTrail extends MenuActiveTrail {

  /**
   * The Context module context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, CacheBackendInterface $cache, LockBackendInterface $lock, ContextManager $context_manager) {
    parent::__construct($menu_link_manager, $route_match, $cache, $lock);
    $this->contextManager = $context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    $found = parent::getActiveLink($menu_name);

    // Get active reaction of Menu type.
    foreach ($this->contextManager->getActiveReactions('menu') as $reaction) {
      $menu_items = $reaction->execute();
      foreach ($menu_items as $menu_link_content) {
        $menu = strtok($menu_link_content, ':');
        if ($menu == $menu_name) {
          $plugin_id = substr($menu_link_content, strlen($menu) + 1);
          return $this->menuLinkManager->createInstance($plugin_id);
        }
      }
    }
    return $found;
  }

}
