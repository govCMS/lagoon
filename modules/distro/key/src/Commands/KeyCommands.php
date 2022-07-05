<?php

namespace Drupal\key\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\key\Plugin\KeyPluginManager;
use Drush\Commands\DrushCommands;
use Drush\Utils\StringUtils;
use Psr\Log\LoggerInterface;

/**
 * Class KeyCommands.
 *
 * @package Drupal\key\Commands
 */
class KeyCommands extends DrushCommands {

  /**
   * Key repository object.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $repository;

  /**
   * Key plugin manager object.
   *
   * @var \Drupal\key\Plugin\KeyPluginManager
   */
  protected $keyTypePluginManager;

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger object.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected ?LoggerInterface $logger = NULL;

  /**
   * Key provider manager object.
   *
   * @var \Drupal\key\Plugin\KeyPluginManager
   */
  protected $keyProviderPluginManager;

  /**
   * Constructs a new KeyCommands drush command.
   */
  public function __construct(
    KeyRepositoryInterface $repository,
    LoggerChannelFactoryInterface $logger,
    KeyPluginManager $key_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager,
    KeyPluginManager $provider_manager
  ) {
    $this->repository = $repository;
    $this->logger = $logger->get('key');
    $this->keyTypePluginManager = $key_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyProviderPluginManager = $provider_manager;
  }

  /**
   * Save a key.
   *
   * @param string $id
   *   The ID (machine name) of the key to save.
   * @param string $key_value
   *   A key value to save. May or may not be allowed or required, depending on
   *   the key provider.
   * @param array $options
   *   Options array.
   *
   * @command key:save
   *
   * @option label The human-readable label of the key.
   * @option description A short description of the key.
   * @option key-type The key type. To see a list of available key types, use `drush key-type-list`.
   * @option key-type-settings Settings specific to the defined key type, in JSON format.
   * @option key-provider The key provider. To see a list of available key providers, use `drush key-provider-list`.
   * @option key-provider-settings Settings specific to the defined key provider, in JSON format.
   * @option key-input The key input method.
   * @option key-input-settings Settings specific to the defined key input, in JSON format.
   * @usage drush key-save secret_password 'pA$$w0rd' --label="Secret password" --key-type=authentication --key-provider=config --key-input=text_field
   *   Define a key for a password to use for authentication using the
   *   Configuration key provider.
   * @usage drush key-save encryption_key --label="Encryption key" --key-type=encryption --key-type-settings='{"key_size":256}' --key-provider=file --key-provider-settings='{"file_location":"private://keys/encryption.key", "base64_encoded":true}' --key-input=none
   *   Define a key to use for encryption using the File key provider.
   * @aliases key-save
   */
  public function save(
    $id,
    $key_value = NULL,
    array $options = [
      'label' => NULL,
      'description' => NULL,
      'key-type' => NULL,
      'key-type-settings' => NULL,
      'key-provider' => NULL,
      'key-provider-settings' => NULL,
      'key-input' => NULL,
      'key-input-settings' => NULL,
    ]
  ) {
    $values = [];
    $values['id'] = $id;

    // Look for a key with the specified ID.
    $existing_key = $this->repository->getKey($values['id']);

    if ($existing_key) {
      // Add a warning about overwriting a key.
      $this->logger->warning('Be extremely careful when overwriting a key! It may result in losing access to a service or making encrypted data unreadable.');

      // Confirm that the key should be saved.
      $this->output->writeln(dt('The following key will be overwritten: !id', ['!id' => $values['id']]));
      if (!$this->io()->confirm(dt('Do you want to continue?'))) {
        // Removing drush_user_abort(), no current implementation of that.
        return;
      }
    }

    // Set any values defined as options.
    foreach (array_keys($this->keyOptions()) as $option) {
      $value = $options[$option];
      if (isset($value)) {
        if (in_array($option, [
          'key-type-settings',
          'key-provider-settings',
          'key-input-settings',
        ])) {
          $values[str_replace('-', '_', $option)] = Json::decode($value);
        }
        else {
          $values[str_replace('-', '_', $option)] = $value;
        }
      }
    }

    // If the label was not defined, use the ID.
    if (!isset($values['label'])) {
      $values['label'] = $values['id'];
    }

    // If the key already exists, make a clone and update it.
    // Otherwise, create a new key entity.
    if ($existing_key) {
      $key = clone $existing_key;
      foreach ($values as $index => $value) {
        if ($index != 'id') {
          $key->set($index, $value);
        }
      }
    }
    else {
      $storage = $this->entityTypeManager->getStorage('key');
      $key = $storage->create($values);
    }

    // If a key value was specified, set it.
    if (isset($key_value)) {
      $key->setKeyValue($key_value);
    }

    // Save the key.
    $key->save();

    // Load the key to confirm that it was saved.
    $key_check = $this->repository->getKey($values['id']);

    if (!$key_check) {
      throw new \Exception(dt('Key !id was not saved.', ['!id' => $values['id']]));
    }

    $this->logger->info('Key !id was saved successfully.', ['!id' => $values['id']]);
  }

  /**
   * Delete a key.
   *
   * @param string $id
   *   The ID (machine name) of the key to delete.
   * @param array $options
   *   Options array.
   *
   * @command key:delete
   *
   * @aliases key-delete
   * @format table
   */
  public function delete($id, array $options = []) {
    // Look for a key with the specified ID. If one does not exist, set an
    // error and abort.
    /* @var $key \Drupal\key\Entity\Key */
    $key = $this->repository->getKey($id);
    if (!$key) {
      throw new \Exception(dt('Key !id does not exist.', ['!id' => $id]));
    }

    // Confirm that the key should be deleted.
    $this->logger->warning('Be extremely careful when deleting a key! It may result in losing access to a service or making encrypted data unreadable.');
    $this->output->writeln(dt('The following key will be deleted: !id', ['!id' => $id]));
    if (!$this->io()->confirm(dt('Do you want to continue?'))) {
      // Removing drush_user_abort(), no current implementation of that.
      return;
    }

    // Delete the key.
    $key->delete();

    // Try to load the key to confirm that it was deleted.
    $key_check = $this->repository->getKey($id);

    // If the key still exists, set an error and abort.
    if ($key_check) {
      throw new \Exception(dt('Key !id was not deleted.', ['!id' => $id]));
    }

    $this->logger->info('Key !id was deleted successfully.', ['!id' => $id]);
  }

  /**
   * Display a list of available keys.
   *
   * @param array $options
   *   Options array.
   *
   * @command key:list
   * @option key-type An optional, comma-delimited list of key types. To see a list of available key types, use `drush key-type-list`.
   * @option key-provider An optional, comma-delimited list of key providers. To see a list of available key providers, use `drush key-provider-list`.
   * @aliases key-list
   * @format table
   */
  public function keyList(array $options = ['key-type' => NULL, 'key-provider' => NULL]) {
    $result = [];

    /* @var $key \Drupal\key\Entity\Key */
    $keys = $this->repository->getKeys();

    // Filter by key type, if specified.
    if ($options['key-type']) {
      $key_type_filter = StringUtils::csvToArray($options['key-type']);
      foreach ($keys as $id => $key) {
        if (!in_array($key->getKeyType()->getPluginId(), $key_type_filter)) {
          unset($keys[$id]);
        }
      }
    }

    // Filter by key provider, if specified.
    if ($options['key-provider']) {
      $key_provider_filter = StringUtils::csvToArray($options['key-provider']);
      foreach ($keys as $id => $key) {
        if (!in_array($key->getKeyProvider()->getPluginId(), $key_provider_filter)) {
          unset($keys[$id]);
        }
      }
    }

    foreach ($keys as $id => $key) {
      $row = [];
      $row['id'] = $id;
      $row['label'] = $key->label();
      $row['key_type'] = $key->getKeyType()->getPluginDefinition()['label'];
      $row['key_provider'] = $key->getKeyProvider()->getPluginDefinition()['label'];
      $result[$id] = $row;
    }

    return new RowsOfFields($result);
  }

  /**
   * Display a list of available key types.
   *
   * @command key:type-list
   * @option group An optional key type group on which to filter.
   * @aliases key-type-list
   * @format table
   */
  public function typeList($options = ['group' => NULL]) {
    $result = [];

    $group = $options['group'];

    $plugins = $this->keyTypePluginManager->getDefinitions();
    foreach ($plugins as $id => $plugin) {
      if (!isset($group) || $plugin['group'] == $group) {
        $row = [];
        $row['id'] = $id;
        $row['description'] = $plugin['description'];

        $result[$id] = $row;
      }
    }

    return new RowsOfFields($result);
  }

  /**
   * Display a list of available key providers.
   *
   * @command key:provider-list
   * @option storage-method An optional key provider storage method on which to filter.
   * @aliases key-provider-list
   * @format table
   */
  public function providerList($options = ['storage-method' => NULL]) {
    $result = [];

    $storage_method = $options['storage-method'];

    $plugins = $this->keyProviderPluginManager->getDefinitions();
    foreach ($plugins as $id => $plugin) {
      if (!isset($storage_method) || $plugin['storage_method'] == $storage_method) {
        $row = [];
        $row['id'] = $id;
        $row['description'] = $plugin['description'];

        $result[$id] = $row;
      }
    }

    return new RowsOfFields($result);
  }

  /**
   * Display a list of available key providers.
   *
   * @param string $id
   *   The ID (machine name) of the key whose value should be retrieved.
   * @param array $options
   *   Options array.
   *
   * @command key:value-get
   *
   * @option base64 Base64-encode the key value. This is useful in the case of binary encryption keys that would otherwise not be displayed in a readable way.
   * @aliases key-value-get, key-value
   * @format table
   */
  public function valueGet($id, array $options = ['base64' => NULL]) {
    $result = [];
    // Look for a key with the specified ID. If one does not exist, set an
    // error and abort.
    /* @var $key \Drupal\key\Entity\Key */
    $key = $this->repository->getKey($id);
    if (!$key) {
      throw new \Exception(dt('Key !id does not exist.', ['!id' => $id]));
    }

    // Retrieve the key value.
    $key_value = $key->getKeyValue(TRUE);

    // If the Base64 option was specified, encode the key value.
    $base64 = $options['base64'];
    if ($base64) {
      $key_value = base64_encode($key_value);
    }

    $row = [];
    $row['Key value'] = $key_value;
    $result[] = $row;

    return new RowsOfFields($result);
  }

  /**
   * Returns array of key options.
   *
   * @return array
   *   Array of key options.
   */
  private function keyOptions() {
    return [
      'label' => [
        'description' => dt('The human-readable label of the key.'),
      ],
      'description' => [
        'description' => dt('A short description of the key.'),
      ],
      'key-type' => [
        'description' => dt('The key type. To see a list of available key types, use `drush key:type-list`.'),
        'example-value' => 'authentication,encryption',
      ],
      'key-type-settings' => [
        'description' => dt('Settings specific to the defined key type, in JSON format.'),
      ],
      'key-provider' => [
        'description' => dt('The key provider. To see a list of available key providers, use `drush key:provider-list`.'),
        'example-value' => 'config,file',
      ],
      'key-provider-settings' => [
        'description' => dt('Settings specific to the defined key provider, in JSON format.'),
      ],
      'key-input' => [
        'description' => dt('The key input method.'),
        'example-value' => 'none,text_field',
      ],
      'key-input-settings' => [
        'description' => dt('Settings specific to the defined key input, in JSON format.'),
      ],
    ];
  }

}
