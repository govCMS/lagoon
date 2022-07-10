<?php

namespace Drupal\config_ignore\Plugin\ConfigFilter;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that reads partly from the active storage.
 *
 * @ConfigFilter(
 *   id = "config_ignore",
 *   label = "Config Ignore",
 *   weight = 100
 * )
 */
class IgnoreFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  const FORCE_EXCLUSION_PREFIX = '~';
  const INCLUDE_SUFFIX = '*';

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * Constructs a new SplitFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active configuration store with the configuration on the site.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $active) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->active = $active;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Get the list of ignored config.
    $ignored = (array) $container->get('config.factory')->get('config_ignore.settings')->get('ignored_config_entities');
    // Allow hooks to alter the list.
    $container->get('module_handler')->invokeAll('config_ignore_settings_alter', [&$ignored]);
    // Set the list in the plugin configuration.
    $configuration['ignored'] = $ignored;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * Match a config entity name against the list of ignored config entities.
   *
   * @param string $config_name
   *   The name of the config entity to match against all ignored entities.
   *
   * @return bool
   *   True, if the config entity is to be ignored, false otherwise.
   */
  protected function matchConfigName($config_name) {
    if (Settings::get('config_ignore_deactivate')) {
      // Allow deactivating config_ignore in settings.php. Do not match any name
      // in that case and allow a normal configuration import to happen.
      return FALSE;
    }

    // If the string is an excluded config, don't ignore it.
    if (in_array(static::FORCE_EXCLUSION_PREFIX . $config_name, $this->configuration['ignored'], TRUE)) {
      return FALSE;
    }

    foreach ($this->configuration['ignored'] as $config_ignore_setting) {
      // Split the ignore settings so that we can ignore individual keys.
      $ignore = explode(':', $config_ignore_setting, 2);
      if (self::wildcardMatch($ignore[0], $config_name)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Read from the active configuration.
   *
   * This method will read the configuration from the active config store.
   * But rather than just straight up returning the value it will check if
   * a nested config key is set to be ignored and set only that value on the
   * data to be filtered.
   *
   * @param string $name
   *   The name of the configuration to read.
   * @param mixed $data
   *   The data to be filtered.
   *
   * @return mixed
   *   The data filtered or read from the active storage.
   */
  protected function activeRead($name, $data) {
    $keys = [];
    foreach ($this->configuration['ignored'] as $ignored) {
      // Split the ignore settings so that we can ignore individual keys.
      $ignored = explode(':', $ignored, 2);
      if (self::wildcardMatch($ignored[0], $name)) {
        if (count($ignored) == 1) {
          // If one of the definitions does not have keys ignore the
          // whole config.
          return $this->active->read($name);
        }
        else {
          // Add the sub parts to ignore to the keys.
          $keys[] = $ignored[1];
        }
      }

    }

    $active = $this->active->read($name);
    if (!$active || !$data) {
      return $data;
    }
    foreach ($keys as $key) {
      $parts = explode('.', $key);

      if (count($parts) == 1) {
        if (isset($active[$key])) {
          $data[$key] = $active[$key];
        }
      }
      else {
        $value = NestedArray::getValue($active, $parts, $key_exists);
        if ($key_exists) {
          // Enforce the value if it existed in the active config.
          NestedArray::setValue($data, $parts, $value, TRUE);
        }
      }
    }

    return $data;
  }

  /**
   * Read multiple from the active storage.
   *
   * @param array $names
   *   The names of the configuration to read.
   * @param array $data
   *   The data to filter.
   *
   * @return array
   *   The new data.
   */
  protected function activeReadMultiple(array $names, array $data) {
    $filtered_data = [];
    foreach ($names as $name) {
      if (!array_key_exists($name, $data)) {
        $data[$name] = [];
      }
      $filtered_data[$name] = $this->activeRead($name, $data[$name]);
    }

    return array_filter($filtered_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    // Read from the active storage when the name is in the ignored list.
    if ($this->matchConfigName($name)) {
      return $this->activeRead($name, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // A name exists if it is ignored and exists in the active storage.
    return $exists || ($this->matchConfigName($name) && $this->active->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    // Limit the names which are read from the active storage.
    $names = array_filter($names, [$this, 'matchConfigName']);
    $active_data = $this->activeReadMultiple($names, $data);

    // Return the data with merged in active data.
    return array_merge($data, $active_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $active_names = $this->active->listAll($prefix);
    // Filter out only ignored config names.
    $active_names = array_filter($active_names, [$this, 'matchConfigName']);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->active->createCollection($collection));
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    // Add active collection names as there could be ignored config in them.
    return array_merge($collections, $this->active->getAllCollectionNames());
  }

  /**
   * Checks if a string matches a given wildcard pattern.
   *
   * @param string $pattern
   *   The wildcard pattern to me matched.
   * @param string $string
   *   The string to be checked.
   *
   * @return bool
   *   TRUE if $string string matches the $pattern pattern.
   */
  protected static function wildcardMatch($pattern, $string) {
    $pattern = '/^' . preg_quote($pattern, '/') . '$/';
    $pattern = str_replace('\*', '.*', $pattern);
    return (bool) preg_match($pattern, $string);
  }

}
