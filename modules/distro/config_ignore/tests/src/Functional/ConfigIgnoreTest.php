<?php

namespace Drupal\Tests\config_ignore\Functional;

use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test functionality of config_ignore module.
 *
 * @package Drupal\Tests\config_ignore\Functional
 *
 * @group config_ignore
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ConfigIgnoreTest extends ConfigIgnoreBrowserTestBase {

  use StringTranslationTrait;

  /**
   * Verify that the Sync. table gets update with appropriate ignore actions.
   */
  public function testSyncTableUpdate() {

    $this->config('system.site')->set('name', 'Test import')->save();
    $this->config('system.date')->set('first_day', '0')->save();
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['system.site'])->save();

    $this->doExport();

    // Login with a user that has permission to sync. config.
    $this->drupalLogin($this->drupalCreateUser(['synchronize configuration']));

    // Change the site name, which is supposed to look as an ignored change
    // in on the sync. page.
    $this->config('system.site')->set('name', 'Test import with changed title')->save();
    $this->config('system.date')->set('first_day', '1')->save();

    // Validate that the sync. table informs the user that the config will be
    // ignored.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->linkExists('Config Ignore Settings');
    /** @var \Behat\Mink\Element\NodeElement[] $table_content */
    $table_content = $this->xpath('//table[@id="edit-ignored"]//td');

    $table_values = [];
    foreach ($table_content as $item) {
      $table_values[] = $item->getHtml();
    }

    $this->assertTrue(in_array('system.site', $table_values));
    $this->assertFalse(in_array('system.date', $table_values));
  }

  /**
   * Verify that the settings form works.
   */
  public function testSettingsForm() {
    // Login with a user that has permission to import config.
    $this->drupalLogin($this->drupalCreateUser(['import configuration']));

    $edit = [
      'ignored_config_entities' => 'config.test_01' . "\r\n" . 'config.test_02',
    ];

    $this->drupalGet('admin/config/development/configuration/ignore');
    $this->submitForm($edit, $this->t('Save configuration'));

    $settings = $this->config('config_ignore.settings')->get('ignored_config_entities');

    $this->assertEquals(['config.test_01', 'config.test_02'], $settings);
  }

  /**
   * Verify that config can get ignored.
   */
  public function testValidateIgnoring() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be ignored upon config import.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['system.site'])->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

  /**
   * Verify all wildcard asterisk is working.
   */
  public function testValidateIgnoringWithWildcard() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be ignored upon config import.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['system.' . IgnoreFilter::INCLUDE_SUFFIX])->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

  /**
   * Verify Force Import syntax is working.
   *
   * This test makes sure we avoid regression issues.
   */
  public function testValidateForceImporting() {
    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = [IgnoreFilter::FORCE_EXCLUSION_PREFIX . 'system.site'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Test import', $this->config('system.site')->get('name'));
  }

  /**
   * Verify excluded configuration works with wildcards.
   *
   * This test cover the scenario where a wildcard matches a specific
   * configuration, but that's still imported due exclusion.
   */
  public function testValidateForceImportingWithWildcard() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = ['system.' . IgnoreFilter::INCLUDE_SUFFIX, IgnoreFilter::FORCE_EXCLUSION_PREFIX . 'system.site'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Test import', $this->config('system.site')->get('name'));

  }

  /**
   * Verify ignoring only some config keys.
   *
   * This test covers the scenario when not the whole config is to be ignored
   * but only a certain subset of it.
   */
  public function testValidateImportingWithIgnoredSubKeys() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')
      ->set('name', 'Test name')
      ->set('slogan', 'Test slogan')
      ->set('page.front', '/ignore')
      ->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = ['system.site:name', 'system.site:page.front'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')
      ->set('name', 'Changed title')
      ->set('slogan', 'Changed slogan')
      ->set('page.front', '/new-ignore')
      ->save();

    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));
    $this->assertEquals('Test slogan', $this->config('system.site')->get('slogan'));
    $this->assertEquals('/new-ignore', $this->config('system.site')->get('page.front'));
  }

}
