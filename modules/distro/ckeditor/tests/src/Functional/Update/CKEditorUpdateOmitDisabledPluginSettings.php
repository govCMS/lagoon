<?php

namespace Drupal\Tests\ckeditor\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path for CKEditor plugin settings for disabled plugins.
 *
 * @group Update
 */
class CKEditorUpdateOmitDisabledPluginSettings extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    // @see https://www.drupal.org/node/3306545
    [$version] = explode('.', \Drupal::VERSION, 2);
    $this->databaseDumpFiles = [
      $version == 9
        ? DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz'
        : DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doInstall() {
    parent::doInstall();

    // TRICKY: ::checkRequirements() runs too early.
    $extensions = Database::getConnection()->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    if (!array_key_exists('ckeditor', $extensions['module'])) {
      $this->markTestSkipped('The CKEditor (4) module has already been uninstalled for this database fixture.');
    }
  }

  /**
   * Ensure settings for disabled CKEditor 4 plugins are omitted on post update.
   */
  public function testUpdateUpdateOmitDisabledSettingsPostUpdate() {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayHasKey('stylescombo', $settings['plugins']);

    $this->runUpdates();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayNotHasKey('stylescombo', $settings['plugins']);
  }

  /**
   * Ensure settings for disabled CKEditor 4 plugins are omitted on entity save.
   */
  public function testUpdateUpdateOmitDisabledSettingsEntitySave() {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayHasKey('stylescombo', $settings['plugins']);
    $editor->save();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayNotHasKey('stylescombo', $settings['plugins']);
  }

}
