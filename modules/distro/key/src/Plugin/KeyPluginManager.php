<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages Key plugins.
 */
class KeyPluginManager extends DefaultPluginManager {

  /**
   * The plugin type being managed.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a KeyPluginManager.
   *
   * @param string $type
   *   The plugin type.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $type_annotations = [
      'key_type' => 'Drupal\key\Annotation\KeyType',
      'key_provider' => 'Drupal\key\Annotation\KeyProvider',
      'key_input' => 'Drupal\key\Annotation\KeyInput',
    ];
    $plugin_interfaces = [
      'key_type' => 'Drupal\key\Plugin\KeyTypeInterface',
      'key_provider' => 'Drupal\key\Plugin\KeyProviderInterface',
      'key_input' => 'Drupal\key\Plugin\KeyInputInterface',
    ];

    $this->pluginType = $type;
    $this->subdir = 'Plugin/' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));

    parent::__construct($this->subdir, $namespaces, $module_handler, $plugin_interfaces[$type], $type_annotations[$type]);
    $this->alterInfo($type . '_info');
    $this->setCacheBackend($cache_backend, $type, ['key_plugins']);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Add plugin_type to the definition.
    $definition['plugin_type'] = $this->pluginType;
  }

}
