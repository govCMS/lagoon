<?php

namespace Drupal\vem_migrate_oembed;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\MediaType;

/**
 * Class VemMigrate.
 */
class VemMigrate {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The last installed schema repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * VemMigrate constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository
   *   The last installed schema repository service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key value store.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository, Connection $connection, KeyValueFactoryInterface $keyValueFactory, EntityFieldManagerInterface $entityFieldManager) {
    $this->configFactory = $configFactory;
    $this->lastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
    $this->database = $connection;
    $this->keyValue = $keyValueFactory;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Migrates from video_embed_media to core media.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function migrate() {

    foreach ($this->configFactory->listAll('media.type.') as $media_type) {
      $media_type = $this->configFactory->getEditable($media_type);

      if ($media_type->get('source') !== 'video_embed_field') {
        continue;
      }

      $media_type->set('source', 'oembed:video');
      $media_type->set('source_configuration.thumbnails_directory', 'public://oembed_thumbnails');

      $media_type->save(TRUE);

      MediaType::load($media_type->get('id'))->calculateDependencies()->save();
    }

    $media_definition = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('media');
    foreach ($this->configFactory->listAll('field.storage.media') as $field_storage) {
      $field_storage = $this->configFactory->getEditable($field_storage);
      $field_name = $field_storage->get('field_name');

      if ($field_storage->get('type') !== 'video_embed_field') {
        continue;
      }

      $this->database->schema()->changeField("media__$field_name", "${field_name}_value", "${field_name}_value", ['type' => 'varchar', 'length' => 255]);
      $this->database->schema()->changeField("media_revision__$field_name", "${field_name}_value", "${field_name}_value", ['type' => 'varchar', 'length' => 255]);

      $store = $this->keyValue->get("entity.storage_schema.sql");
      $data = $store->get("media.field_schema_data.$field_name");
      $data["media__$field_name"]['fields']["${field_name}_value"]['length'] = 255;
      $data["media_revision__$field_name"]['fields']["${field_name}_value"]['length'] = 255;
      $store->set("media.field_schema_data.$field_name", $data);

      $media_definition[$field_name]->set('type', 'string');
      $this->lastInstalledSchemaRepository->setLastInstalledFieldStorageDefinition($media_definition[$field_name]);

      $this->entityFieldManager->clearCachedFieldDefinitions();

      $field_storage->set('type', 'string');
      $field_storage->set('module', 'core');
      $field_storage->set('settings.max_length', 255);
      $field_storage->set('settings.is_ascii', FALSE);
      $field_storage->set('settings.case_sensitive', FALSE);
      $field_storage->save(TRUE);

      FieldStorageConfig::loadByName('media', $field_name)->calculateDependencies()->save();
    }

    foreach ($this->configFactory->listAll('field.field.media') as $field_config) {
      $field_config = $this->configFactory->getEditable($field_config);

      if ($field_config->get('field_type') !== 'video_embed_field') {
        continue;
      }

      $field_config->set('field_type', 'string');
      $field_config->set('settings', []);
      $field_config->save(TRUE);

      FieldConfig::loadByName('media', $field_config->get('bundle'), $field_config->get('field_name'))->calculateDependencies()->save();
    }

    foreach ($this->configFactory->listAll('core.entity_view_display.media') as $entity_view_display) {
      $entity_view_display = $this->configFactory->getEditable($entity_view_display);

      $fields = array_keys($entity_view_display->get('content'));
      foreach ($fields as $field) {
        if ($entity_view_display->get("content.$field.type") !== 'video_embed_field_video') {
          continue;
        }

        $entity_view_display->set("content.$field.type", 'oembed');
        $entity_view_display->set("content.$field.settings.max_width", $entity_view_display->get("content.$field.settings.width"));
        $entity_view_display->set("content.$field.settings.max_height", $entity_view_display->get("content.$field.settings.height"));
        $entity_view_display->clear("content.$field.settings.autoplay");
        $entity_view_display->clear("content.$field.settings.responsive");
        $entity_view_display->save(TRUE);

        $bundle = $entity_view_display->get('bundle');
        $mode = $entity_view_display->get('mode');
        EntityViewDisplay::load("media.$bundle.$mode")->calculateDependencies()->save();
      }

    }

    foreach ($this->configFactory->listAll('core.entity_form_display.media') as $entity_form_display) {
      $entity_form_display = $this->configFactory->getEditable($entity_form_display);

      $fields = array_keys($entity_form_display->get('content'));
      foreach ($fields as $field) {
        if ($entity_form_display->get("content.$field.type") !== 'video_embed_field_textfield') {
          continue;
        }

        $entity_form_display->set("content.$field.type", 'oembed_textfield');
        $entity_form_display->set("content.$field.settings.size", 60);
        $entity_form_display->set("content.$field.settings.placeholder", '');
        $entity_form_display->save(TRUE);

        $bundle = $entity_form_display->get('bundle');
        $mode = $entity_form_display->get('mode');
        EntityFormDisplay::load("media.$bundle.$mode")->calculateDependencies()->save();
      }

    }
  }

}
