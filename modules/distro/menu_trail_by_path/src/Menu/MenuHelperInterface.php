<?php

namespace Drupal\menu_trail_by_path\Menu;

interface MenuHelperInterface {
  /**
   * @param $menu_name
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   */
  public function getMenuLinks($menu_name);
}
