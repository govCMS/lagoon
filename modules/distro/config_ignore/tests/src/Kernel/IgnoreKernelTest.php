<?php

namespace Drupal\Tests\config_ignore\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test the transformations.
 *
 * This test is a bit more condensed and doesn't actually import the config.
 *
 * @group config_ignore_new
 */
class IgnoreKernelTest extends KernelTestBase {

  use ConfigStorageTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'language',
    'config',
    'config_test',
    'config_ignore',
    'config_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // We install the system and config_test config so that there is something
    // to modify and ignore for the test.
    $this->installConfig(['system', 'config_test']);

    // Set up multilingual. The config_test module comes with translations.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test the import transformations.
   *
   * @param array $modes
   *   The import modes.
   * @param array $patterns
   *   An array of ignore patterns, we may refactor this to be the whole config.
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @dataProvider importProvider
   */
  public function testImport(array $modes, array $patterns, array $active, array $sync, array $expected) {
      $this->config('config_ignore.settings')->set('ignored_config_entities', $patterns)->save();

      $expectedStorage = $this->setUpStorages($active, $sync, $expected);

      static::assertStorageEquals($expectedStorage, $this->getImportStorage());
  }

  /**
   * Provides the test cases for the import.
   *
   * @return array
   *   The test case.
   */
  public function importProvider() {
    return [
      'empty test' => [
        // Modes, these are not implemented yet.
        [],
        // The ignore config.
        [],
        // Modifications to the active config keyed by language.
        [],
        // Modifications to the sync config keyed by language.
        [],
        // Modifications to the expected config keyed by language.
        [],
      ],
      'keep config deleted in sync' => [
        [],
        ['config_test.system'],
        [],
        [
          // Delete the config_test.system from all languages in sync storage.
          '' => ['config_test.system' => FALSE],
          'de' => ['config_test.system' => FALSE],
          'fr' => ['config_test.system' => FALSE],
        ],
        [],
      ],
      'remove translation when not ignored' => [
        [],
        ['config_test.system'],
        ['de' => ['config_test.no_status.default' => ['label' => 'DE default']]],
        [],
        [],
      ],
      'do not remove translation when ignored' => [
        [],
        ['config_test.system'],
        ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
        [],
        ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
      ],
      'do not remove translation when key is ignored' => [
        [],
        ['config_test.system:foo'],
        ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
        [],
        ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
      ],
      'remove translation when other key is ignored' => [
        [],
        ['config_test.system:404'],
        ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
        [],
        [],
      ],
      'new translation is ignored' => [
        ['strict'],
        ['config_test.*'],
        [],
        ['se' => ['config_test.system' => ['foo' => 'Ny foo']]],
        [],
      ],
      'new config is ignored' => [
        ['strict'],
        ['config_test.*'],
        [
          '' => [
            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
          ],
        ],
        [
          '' => [
            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N'],
            'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
            'config_test.system' => ['foo' => 'ignored']
          ],
        ],
        [
          '' => [
            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
          ],
        ],
      ],
//      'new config is not ignored in lenient mode' => [
//        ['lenient'],
//        ['config_test.*'],
//        [
//          '' => [
//            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
//          ],
//        ],
//        [
//          '' => [
//            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N'],
//            'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
//            'config_test.system' => ['foo' => 'ignored']
//          ],
//        ],
//        [
//          '' => [
//            'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
//            'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
//          ],
//        ],
//      ],
      'new config with only key ignored (issue 3137437)' => [
        ['strict'],
        ['config_test.*:label'],
        ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
        [],
        [],
      ],
//      'new config with  only key ignored lenient (issue 3137437)' => [
//        ['lenient'],
//        ['config_test.*:label'],
//        ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
//        [],
//        ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
//      ],
    ];
  }

  /**
   * Test the export transformations.
   *
   * @param string $mode
   *   The export mode
   * @param array $patterns
   *   An array of ignore patterns, we may refactor this to be the whole config.
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @dataProvider exportProvider
   */
  public function testExport(array $modes, array $patterns, array $active, array $sync, array $expected) {
    $this->config('config_ignore.settings')->set('ignored_config_entities', $patterns)->save();

    $expectedStorage = $this->setUpStorages($active, $sync, $expected);

    static::assertStorageEquals($expectedStorage, $this->getExportStorage());
  }

  /**
   * Provides the test cases for the export.
   *
   * @return array
   */
  public function exportProvider() {
    // @todo: add meaningful tests in https://www.drupal.org/project/config_ignore/issues/2857247
    return [
      'empty test' => [
        // For now exporting is always off.
        ['off'],
        // The ignore config.
        [],
        // Modifications to the active config keyed by language.
        [],
        // Modifications to the sync config keyed by language.
        [],
        // Modifications to the expected config keyed by language.
        [],
      ],
    ];
  }

  /**
   * Set up the active, sync and expected storages.
   *
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The expected storage.
   */
  protected function setUpStorages(array $active, array $sync, array $expected): StorageInterface {
    // Copy the active config to the sync storage and the expected storage.
    $syncStorage = $this->getSyncFileStorage();
    $expectedStorage = new MemoryStorage();
    $this->copyConfig($this->getActiveStorage(), $syncStorage);
    $this->copyConfig($this->getActiveStorage(), $expectedStorage);

    // Then modify the active storage by saving the config which was given.
    foreach ($active as $lang => $configs) {
      foreach ($configs as $name => $data) {
        if ($lang === '') {
          $config = $this->config($name);
        }
        else {
          // Load the config override.
          $config = \Drupal::languageManager()->getLanguageConfigOverride($lang, $name);
        }

        if ($data !== FALSE) {
          $config->merge($data)->save();
        }
        else {
          // If the data is not an array we want to delete it.
          $config->delete();
        }
      }
    }

    // Apply modifications to the storages.
    static::modifyStorage($syncStorage, $sync);
    static::modifyStorage($expectedStorage, $expected);

    return $expectedStorage;
  }

  /**
   * Helper method to modify a config storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to modify.
   * @param array $modifications
   *   The modifications keyed by language.
   */
  protected static function modifyStorage(StorageInterface $storage, array $modifications) {
    foreach ($modifications as $lang => $configs) {
      $lang = $lang === '' ? StorageInterface::DEFAULT_COLLECTION : 'language.' . $lang;
      $storage = $storage->createCollection($lang);
      if ($configs === NULL) {
        // If it is set to null explicitly remove everything.
        $storage->deleteAll();
        return;
      }
      foreach ($configs as $name => $data) {
        if ($data !== FALSE) {
          if (is_array($storage->read($name))) {
            // Merge nested arrays if the storage already has data.
            $data = NestedArray::mergeDeep($storage->read($name), $data);
          }
          $storage->write($name, $data);
        }
        else {
          // A config name set to false means deleting it.
          $storage->delete($name);
        }
      }
    }
  }

}
