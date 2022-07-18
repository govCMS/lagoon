<?php

namespace Drupal\entity_hierarchy_microsite\Plugin\Menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for a menu item based on hierarchy.
 */
class MicrositeMenuItem extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
    'title' => 1,
  ];

  /**
   * The menu link content entity connected to this plugin instance.
   *
   * @var \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface
   */
  protected $overrideEntity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructs a new MicrositeMenuItem.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    if (!$this->getOverrideEntity()->isNew()) {
      $this->getOverrideEntity()->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    if (!$this->getOverrideEntity()->isNew()) {
      return $this->getOverrideEntity()->toUrl('delete-form');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    if (!$this->getOverrideEntity()->isNew()) {
      return $this->getOverrideEntity()->toUrl('edit-form');
    }
    return new Url('entity.eh_microsite_menu_override.add', ['target' => $this->getDerivativeId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    // We don't allow resetting, but instead allow deleting.
    return FALSE;
  }

  /**
   * Loads the override entity associated with this menu link.
   *
   * @return \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface
   *   The menu link override
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and UUID are both invalid or missing.
   */
  protected function getOverrideEntity() {
    $storage = $this->entityTypeManager->getStorage('eh_microsite_menu_override');
    if (empty($this->overrideEntity)) {
      if ($items = $storage->loadByProperties([
        'target' => $this->getDerivativeId(),
      ])) {
        $this->overrideEntity = reset($items);
        return $this->overrideEntity;
      }

      $this->overrideEntity = $storage->create([
        'target' => $this->getDerivativeId(),
        'enabled' => $this->isEnabled(),
        'weight' => $this->getWeight(),
        'title' => $this->getTitle(),
        'parent' => $this->getParent(),
      ]);
    }
    return $this->overrideEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $original = $this->getPluginDefinition();
    $metadata = $original['metadata'] + [
      'original' => array_intersect_key($original, [
        'title' => TRUE,
        'weight' => TRUE,
        'enabled' => TRUE,
        'expanded' => TRUE,
        'parent' => TRUE,
      ]),
    ];
    $this->pluginDefinition = ['metadata' => $metadata] + $overrides + $original;
    if ($persist) {
      $overrideEntity = $this->getOverrideEntity();
      foreach ($overrides as $key => $value) {
        $overrideEntity->{$key}->value = $value;
      }
      $overrideEntity->setSyncing(TRUE);
      $overrideEntity->save();
      $overrideEntity->setSyncing(FALSE);
    }

    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    $url = parent::getUrlObject($title_attribute);
    $override_entity = $this->getOverrideEntity();
    $this->moduleHandler->alter('entity_hierarchy_microsite_menu_item_url', $url, $override_entity, $this);
    return $url;
  }

}
