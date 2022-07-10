<?php

namespace Drupal\Tests\config_ignore\Functional;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Class ConfigIgnoreBrowserTestBase.
 *
 * @package Drupal\Tests\config_ignore
 */
abstract class ConfigIgnoreBrowserTestBase extends BrowserTestBase {

  use ConfigStorageTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config_ignore', 'config', 'config_filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform a config import from sync. folder.
   */
  public function doImport() {
    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->getImportStorage(),
      $this->container->get('config.storage')
    );

    $config_importer = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module')
    );

    $config_importer->reset()->import();
  }

  /**
   * Perform a config export to sync. folder.
   */
  public function doExport() {
    // Export the config using the export storage service.
    $this->copyConfig($this->getExportStorage(), $this->getSyncFileStorage());
  }

}
