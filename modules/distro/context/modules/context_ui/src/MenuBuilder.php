<?php

namespace Drupal\context_ui;

use Drupal\context\Entity\Context;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Implements the MenuBuilder class.
 *
 * MenuBuilder configures and updates the submenu context items.
 *
 * @package Drupal\context_ui
 */
class MenuBuilder {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * MenuBuilder constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The menu link plugin manager.
   */
  public function __construct(MenuLinkManagerInterface $menuLinkManager) {
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * Adds a submenu item for the $entity item.
   *
   * @param \Drupal\context\Entity\Context $entity
   *   The given entity item.
   */
  public function addSubMenuItem(Context $entity) {
    $menu_link = MenuLinkContent::create([
      'title'     => $entity->getLabel(),
      'link'      => $this->getUriString($entity),
      'menu_name' => 'admin',
      'parent'    => 'entity.context.collection',
      'expanded'  => TRUE,
      'weight'    => 10,
    ]);
    $menu_link->save();
    $this->menuLinkManager->rebuild();
  }

  /**
   * Updates the submenu item of the $entity item.
   *
   * @param \Drupal\context\Entity\Context $entity
   *   The given entity item.
   */
  public function updateSubMenuItem(Context $entity) {
    $result = $this->menuLinkManager->loadLinksByRoute('entity.context.edit_form', ['context' => $entity->id()]);
    if (!empty($result)) {
      foreach ($result as $id => $instance) {
        if (strpos($id, 'menu_link_content:') === 0) {
          $instance->updateLink([
            'title' => $entity->getLabel(),
            'link'  => $this->getUriString($entity),
          ], TRUE);
        }
      }
      $this->menuLinkManager->rebuild();
    }
    else {
      $this->addSubMenuItem($entity);
    }

  }

  /**
   * Deletes the submenu item of the $entity item.
   *
   * @param \Drupal\context\Entity\Context $entity
   *   The given entity item.
   */
  public function deleteSubMenuItem(Context $entity) {
    $result = $this->menuLinkManager->loadLinksByRoute('entity.context.edit_form', ['context' => $entity->id()]);
    if (!empty($result)) {
      foreach ($result as $id => $instance) {
        if ($instance->isDeletable() && strpos($id, 'menu_link_content:') === 0) {
          $instance->deleteLink();
          $this->menuLinkManager->rebuild();
        }
      }
    }
    $this->menuLinkManager->rebuild();
  }

  /**
   * Return the URI string of the given context entity.
   *
   * @param \Drupal\context\Entity\Context $entity
   *   The context entity.
   *
   * @return string
   *   The URI string.
   */
  private function getUriString(Context $entity) {
    $url = Url::fromRoute('entity.context.edit_form', ['context' => $entity->id()]);
    return $url->toUriString();
  }

}
