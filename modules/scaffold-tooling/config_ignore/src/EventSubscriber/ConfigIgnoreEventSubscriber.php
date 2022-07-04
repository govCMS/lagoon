<?php

namespace Drupal\config_ignore\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Makes the import/export aware of ignored configs.
 */
class ConfigIgnoreEventSubscriber implements EventSubscriberInterface, CacheTagsInvalidatorInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The sync config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * Statically cached ignored config patterns and exceptions.
   *
   * Null if not cached, or a keyed array containing:
   * - 0: (string[]) Array of config ignore patterns
   * - 1: (string[]) Exceptions to config ignore patterns.
   *
   * @var array|null
   */
  protected $ignoredConfig = NULL;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config active storage.
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The sync config storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StorageInterface $config_storage, StorageInterface $sync_storage) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->activeStorage = $config_storage;
    $this->syncStorage = $sync_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform'],
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => ['onExportTransform'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // Invalidate static cache if config changes.
    if (in_array('config:config_ignore.settings', $tags, TRUE)) {
      $this->ignoredConfig = NULL;
    }
  }

  /**
   * Acts when the storage is transformed for import.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    if (!Settings::get('config_ignore_deactivate')) {
      $this->transformStorage($event->getStorage(), $this->activeStorage);
    }
  }

  /**
   * Acts when the storage is transformed for export.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    if (!Settings::get('config_ignore_deactivate')) {
      $this->transformStorage($event->getStorage(), $this->syncStorage);
    }
  }

  /**
   * Makes the import or export storages aware about ignored configs.
   *
   * @param \Drupal\Core\Config\StorageInterface $transformation_storage
   *   The import or the export storage.
   * @param \Drupal\Core\Config\StorageInterface $destination_storage
   *   The active storage on import. The sync storage on export.
   */
  protected function transformStorage(StorageInterface $transformation_storage, StorageInterface $destination_storage) {
    $collection_names = array_unique(array_merge($transformation_storage->getAllCollectionNames(), $destination_storage->getAllCollectionNames()));
    array_unshift($collection_names, StorageInterface::DEFAULT_COLLECTION);

    foreach ($collection_names as $collection_name) {
      $destination_storage = $destination_storage->createCollection($collection_name);
      $transformation_storage = $transformation_storage->createCollection($collection_name);

      // Loop over the ignored config in the destination.
      // We need to do this inside of the collection loop because some config
      // to be ignored may only be present in some collections.
      $destination_ignored = $this->getIgnoredConfigs($destination_storage);
      foreach ($destination_ignored as $config_name => $keys) {
        // We just calculated the ignored config based on the storage.
        assert($destination_storage->exists($config_name), "The configuration $config_name exists");
        $destination_data = $destination_storage->read($config_name);
        if ($keys === NULL) {
          // The entire config is ignored.
          $transformation_storage->write($config_name, $destination_data);
        }
        else {
          // Only some keys are ignored.
          $source_data = $transformation_storage->read($config_name);
          if ($source_data === FALSE) {
            // The config doesn't exist in the transformation storage but only
            // a key is ignored, we skip writing anything to the transformation
            // storage. But this could be made configurable.
            continue;
          }
          foreach ($keys as $key) {
            if (NestedArray::keyExists($destination_data, $key)) {
              $value = NestedArray::getValue($destination_data, $key);
              NestedArray::setValue($source_data, $key, $value);
            }
            else {
              NestedArray::unsetValue($source_data, $key);
            }
          }
          $transformation_storage->write($config_name, $source_data);
        }
      }

      // Now we get the config to be ignored which exists only in the
      // transformation storage. When importing this means that it is new and
      // when exporting it means it does not exist in the sync directory.
      $transformation_only_ignored = array_diff_key($this->getIgnoredConfigs($transformation_storage), $destination_ignored);
      foreach ($transformation_only_ignored as $config_name => $keys) {
        if ($keys === NULL) {
          // The entire config is ignored.
          $transformation_storage->delete($config_name);
        }
        else {
          // Only some keys are ignored.
          $source_data = $transformation_storage->read($config_name);
          foreach ($keys as $key) {
            NestedArray::unsetValue($source_data, $key);
          }
          $transformation_storage->write($config_name, $source_data);
        }
      }
    }
  }

  /**
   * Returns the list of all ignored configs by expanding the wildcards.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A config storage.
   *
   * @return array
   *   An associative array keyed by config name and having the values either
   *   NULL, if the whole config is ignored, or an array of keys to be ignored.
   *   Each key is an array of parents:
   *   @code
   *   [
   *     'system.site' => NULL,
   *     'user.settings' => [
   *       ['notify', 'cancel_confirm'],
   *       ['password_reset_timeout'],
   *     ],
   *   ]
   *   @endcode
   */
  protected function getIgnoredConfigs(StorageInterface $storage) {
    [$patterns, $exceptions] = $this->getRules();

    $ignored_configs = [];
    foreach ($storage->listAll() as $config_name) {
      foreach ($patterns as $ignored_config_pattern) {
        if (strpos($ignored_config_pattern, ':') !== FALSE) {
          // Some patterns are defining also a key.
          [$config_name_pattern, $key] = explode(':', $ignored_config_pattern, 2);
          $key = trim($key);
          if (strpos($key, '*') !== FALSE) {
            throw new \LogicException("The key part of the config ignore pattern cannot contain the wildcard character '*'.");
          }
        }
        else {
          $config_name_pattern = $ignored_config_pattern;
          $key = NULL;
        }
        if ($this->wildcardMatch($config_name_pattern, $config_name)) {
          if ($key) {
            $ignored_configs[$config_name][] = explode('.', $key);
          }
          else {
            $ignored_configs[$config_name] = NULL;
            // As this pattern has no key we continue with the next config. Any
            // subsequent pattern with the same config but with key is covered
            // by this ignore pattern.
            break;
          }
        }
      }
    }

    // Extract the exceptions from the ignored configs.
    return array_diff_key($ignored_configs, array_flip($exceptions));
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
  protected function wildcardMatch($pattern, $string) {
    $pattern = '/^' . preg_quote($pattern, '/') . '$/';
    $pattern = str_replace('\*', '.*', $pattern);
    return (bool) preg_match($pattern, $string);
  }

  /**
   * Get config ignore rules.
   *
   * @return array
   *   A keyed array containing:
   *   - 0: (string[]) Array of config ignore patterns
   *   - 1: (string[]) Exceptions to config ignore patterns.
   */
  protected function getRules() {
    if (isset($this->ignoredConfig)) {
      return $this->ignoredConfig;
    }

    $ignored_configs_patterns = $this->configFactory->get('config_ignore.settings')->get('ignored_config_entities');
    $this->moduleHandler->invokeAll('config_ignore_settings_alter', [&$ignored_configs_patterns]);

    // Builds ignored configs exceptions and remove them from the pattern list.
    $exceptions = [];
    foreach ($ignored_configs_patterns as $delta => $ignored_config_pattern) {
      if (strpos($ignored_config_pattern, '~') === 0) {
        if (strpos($ignored_config_pattern, '*') !== FALSE) {
          throw new \LogicException("A config ignore pattern entry cannot contain both, '~' and '*'.");
        }
        $exceptions[] = substr($ignored_config_pattern, 1);
        unset($ignored_configs_patterns[$delta]);
      }
    }
    $this->ignoredConfig = [$ignored_configs_patterns, $exceptions];

    return $this->ignoredConfig;
  }

}
