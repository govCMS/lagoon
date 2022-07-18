<?php

namespace Drupal\menu_trail_by_path;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\menu_trail_by_path\Menu\MenuHelperInterface;
use Drupal\menu_trail_by_path\Path\PathHelperInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\system\Entity\Menu;

/**
 * Overrides the class for the file entity normalizer from HAL.
 */
class MenuTrailByPathActiveTrail extends MenuActiveTrail {

  /**
   * Disabled menu trail.
   */
  const MENU_TRAIL_DISABLED = 'disabled';

  /**
   * Menu trail is created using this module.
   */
  const MENU_TRAIL_PATH = 'path';

  /**
   * Menu trail is created by Drupal Core.
   */
  const MENU_TRAIL_CORE = 'core';

  /**
   * @var \Drupal\menu_trail_by_path\Path\PathHelperInterface
   */
  protected $pathHelper;

  /**
   * @var \Drupal\menu_trail_by_path\Menu\MenuHelperInterface
   */
  protected $menuHelper;

  /**
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MenuTrailByPathActiveTrail constructor.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\menu_trail_by_path\Path\PathHelperInterface $path_helper
   * @param \Drupal\menu_trail_by_path\Menu\MenuHelperInterface $menu_helper
   * @param \Drupal\Core\Routing\RequestContext $context
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, CacheBackendInterface $cache, LockBackendInterface $lock, PathHelperInterface $path_helper, MenuHelperInterface $menu_helper, RequestContext $context, LanguageManagerInterface $languageManager, ConfigFactoryInterface $config_factory) {
    parent::__construct($menu_link_manager, $route_match, $cache, $lock);
    $this->pathHelper      = $path_helper;
    $this->menuHelper      = $menu_helper;
    $this->context         = $context;
    $this->languageManager = $languageManager;
    $this->config = $config_factory->get('menu_trail_by_path.settings');
  }

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/node/2824594
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      return parent::getCid() . ":langcode:{$this->languageManager->getCurrentLanguage()->getId()}:pathinfo:{$this->context->getPathInfo()}";
    }

    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetActiveTrailIds($menu_name) {
    // Parent ids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with parent == ''.
    $active_trail = array('' => '');

    $entity = Menu::load($menu_name);
    if (!$entity) {
      return $active_trail;
    }

    // Build an active trail based on the trail source setting.
    $trail_source = $entity->getThirdPartySetting('menu_trail_by_path', 'trail_source') ?: $this->config->get('trail_source');
    if ($trail_source == static::MENU_TRAIL_CORE) {
      return parent::doGetActiveTrailIds($menu_name);
    }
    elseif ($trail_source == static::MENU_TRAIL_DISABLED) {
      return $active_trail;
    }

    // If a link in the given menu indeed matches the path, then use it to
    // complete the active trail.
    if ($active_link = $this->getActiveTrailLink($menu_name)) {
      if ($parents = $this->menuLinkManager->getParentIds($active_link->getPluginId())) {
        $active_trail = $parents + $active_trail;
      }
    }

    return $active_trail;
  }

  /**
   * Fetches the deepest, heaviest menu link which matches the deepest trail path url.
   *
   * @param string $menu_name
   *   The menu within which to find the active trail link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|NULL
   *   The menu link for the given menu, or NULL if there is no matching menu link.
   */
  public function getActiveTrailLink($menu_name) {
    $menu_links = $this->menuHelper->getMenuLinks($menu_name);
    $trail_urls = $this->pathHelper->getUrls();

    foreach (array_reverse($trail_urls) as $trail_url) {
      foreach (array_reverse($menu_links) as $menu_link) {
        /* @var $menu_link \Drupal\Core\Menu\MenuLinkInterface */
        /* @var $trail_url \Drupal\Core\Url */
        if ($menu_link->getUrlObject()->toString() == $trail_url->toString()) {
          return $menu_link;
        }
      }
    }

    return NULL;
  }
}
