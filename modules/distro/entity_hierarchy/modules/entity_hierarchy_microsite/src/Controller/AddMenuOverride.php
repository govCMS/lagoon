<?php

namespace Drupal\entity_hierarchy_microsite\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a class for a controller for adding a menu overrride.
 */
class AddMenuOverride implements ContainerInjectionInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  private $entityFormBuilder;

  /**
   * Menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  private $menuLinkManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new AddMenuOverride.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   Entity form builder.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   Menu link manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityFormBuilderInterface $entityFormBuilder, MenuLinkManagerInterface $menuLinkManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityFormBuilder = $entityFormBuilder;
    $this->menuLinkManager = $menuLinkManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('plugin.manager.menu.link'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Route callback for adding a new override.
   *
   * @param string $target
   *   Target UUID.
   *
   * @return array
   *   Form.
   */
  public function addMenuOverride(string $target) {
    $plugin_id = 'entity_hierarchy_microsite:' . $target;
    if (!$this->menuLinkManager->hasDefinition($plugin_id)) {
      throw new NotFoundHttpException();
    }
    $storage = $this->entityTypeManager->getStorage('eh_microsite_menu_override');
    if ($storage->loadByProperties([
      'target' => $target,
    ])) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\entity_hierarchy_microsite\Plugin\Menu\MicrositeMenuItem $instance */
    $instance = $this->menuLinkManager->createInstance($plugin_id);
    $override = $storage->create([
      'target' => $target,
      'enabled' => $instance->isEnabled(),
      'weight' => $instance->getWeight(),
      'title' => $instance->getTitle(),
      'parent' => $instance->getParent(),
    ]);

    return $this->entityFormBuilder->getForm($override);
  }

  /**
   * Title callback for adding a new override.
   *
   * @param string $target
   *   Target UUID.
   *
   * @return string
   *   Title.
   */
  public function title(string $target) {
    $plugin_id = 'entity_hierarchy_microsite:' . $target;
    if (!$this->menuLinkManager->hasDefinition($plugin_id)) {
      return '';
    }

    /** @var \Drupal\entity_hierarchy_microsite\Plugin\Menu\MicrositeMenuItem $instance */
    $instance = $this->menuLinkManager->createInstance($plugin_id);
    return new TranslatableMarkup('Add an override for @label', [
      '@label' => $instance->getTitle(),
    ]);
  }

}
