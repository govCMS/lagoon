<?php

namespace Drupal\menu_trail_by_path\Path;

interface PathHelperInterface {
  /**
   * @return \Drupal\Core\Url[]
   */
  public function getUrls();

  /**
   * Returns a list of path elements based on the maximum path parts setting.
   *
   * @return string[]
   *   A list of path elements.
   */
  public function getPathElements();

}
