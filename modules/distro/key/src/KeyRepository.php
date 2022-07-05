<?php

namespace Drupal\key;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a repository for Key configuration entities.
 */
class KeyRepository implements KeyRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The key provider plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $keyProviderManager;

  /**
   * The key type plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $keyTypeManager;

  /**
   * The key input plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $keyInputManager;

  /**
   * Constructs a new KeyRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $key_provider_manager
   *   The key provider plugin manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $key_type_manager
   *   The key type plugin manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $key_input_manager
   *   The key input plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $key_provider_manager, PluginManagerInterface $key_type_manager, PluginManagerInterface $key_input_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyProviderManager = $key_provider_manager;
    $this->keyTypeManager = $key_type_manager;
    $this->keyInputManager = $key_input_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(array $key_ids = NULL) {
    return $this->entityTypeManager->getStorage('key')->loadMultiple($key_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeysByProvider($key_provider_id) {
    return $this->entityTypeManager->getStorage('key')->loadByProperties(['key_provider' => $key_provider_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeysByType($key_type_id) {
    return $this->entityTypeManager->getStorage('key')->loadByProperties(['key_type' => $key_type_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeysByStorageMethod($storage_method) {
    $key_providers = array_filter($this->keyProviderManager->getDefinitions(), function ($definition) use ($storage_method) {
      return $definition['storage_method'] == $storage_method;
    });

    $keys = [];
    foreach ($key_providers as $key_provider) {
      $keys = array_merge($keys, $this->getKeysByProvider($key_provider['id']));
    }
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeysByTypeGroup($type_group) {
    $key_types = array_filter($this->keyTypeManager->getDefinitions(), function ($definition) use ($type_group) {
      return $definition['group'] == $type_group;
    });

    $keys = [];
    foreach ($key_types as $key_type) {
      $keys = array_merge($keys, $this->getKeysByType($key_type['id']));
    }
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($key_id) {
    return $this->entityTypeManager->getStorage('key')->load($key_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyNamesAsOptions(array $filters = []) {
    $options = [];
    $keys = $this->getKeys();

    foreach ($filters as $index => $filter) {
      switch ($index) {
        case 'type':
          $keys = array_intersect_key($this->getKeysByType($filter), $keys);
          break;

        case 'provider':
          $keys = array_intersect_key($this->getKeysByProvider($filter), $keys);
          break;

        case 'type_group':
          $keys = array_intersect_key($this->getKeysByTypeGroup($filter), $keys);
          break;

        case 'storage_method':
          $keys = array_intersect_key($this->getKeysByStorageMethod($filter), $keys);
          break;
      }
    }

    foreach ($keys as $key) {
      $key_id = $key->id();
      $key_title = $key->label();
      $options[$key_id] = (string) $key_title;
    }

    return $options;
  }

}
