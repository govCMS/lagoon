<?php

namespace Drupal\key;

/**
 * Provides the interface for a repository of Key entities.
 */
interface KeyRepositoryInterface {

  /**
   * Get Key entities.
   *
   * @param array $key_ids
   *   (optional) An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\key\Entity\Key[]
   *   An array of key entities, indexed by ID. Returns an empty array if no
   *   matching entities are found.
   */
  public function getKeys(array $key_ids = NULL);

  /**
   * Get keys that use the specified key provider.
   *
   * @param string $key_provider_id
   *   The key provider ID to use.
   *
   * @return \Drupal\key\Entity\Key[]
   *   An array of key objects indexed by their ids.
   */
  public function getKeysByProvider($key_provider_id);

  /**
   * Get keys that use the specified key type.
   *
   * @param string $key_type_id
   *   The key type ID to use.
   *
   * @return \Drupal\key\Entity\Key[]
   *   An array of key objects indexed by their ids.
   */
  public function getKeysByType($key_type_id);

  /**
   * Get keys that use the specified storage method.
   *
   * Storage method is an annotation of a key's key provider.
   *
   * @param string $storage_method
   *   The storage method of the key provider.
   *
   * @return \Drupal\key\Entity\Key[]
   *   An array of key objects indexed by their ids.
   */
  public function getKeysByStorageMethod($storage_method);

  /**
   * Get keys that use a key type in the specified group.
   *
   * Group is an annotation of a key's key type.
   *
   * @param string $type_group
   *   The key type group on which to filter.
   *
   * @return \Drupal\key\Entity\Key[]
   *   An array of key objects indexed by their ids.
   */
  public function getKeysByTypeGroup($type_group);

  /**
   * Get a specific key.
   *
   * @param string $key_id
   *   The key ID to use.
   *
   * @return \Drupal\key\Entity\Key
   *   The key object with the given id.
   */
  public function getKey($key_id);

  /**
   * Get an array of key names, useful as options in form fields.
   *
   * @param array $filters
   *   An array of filters to apply to the list of options.
   *
   * @return array
   *   An array of key names, indexed by id.
   */
  public function getKeyNamesAsOptions(array $filters);

}
